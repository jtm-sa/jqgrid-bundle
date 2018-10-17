<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle\Util;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class EntityFinder
{
    public const NULL_FIELD_VALUE = '_null';
    private const ENTITY_ALIAS = 'e';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var QueryBuilder */
    protected $builder;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function prepareBuilder(array $columns, string $entityName, callable $scope = null): self
    {
        if (!\class_exists($entityName)) {
            throw new \LogicException('The entity class isn\'t exists.');
        }

        if (\count($columns) === 0) {
            $select = self::ENTITY_ALIAS;
        } else {
            $select = 'partial ' . self::ENTITY_ALIAS . '.{' . \implode(',', $columns) . '}';
        }

        $this->builder = $this->entityManager
            ->createQueryBuilder()
            ->select($select)
            ->from($entityName, self::ENTITY_ALIAS);

        if ($scope !== null) {
            $scope($this->builder);
        }

        return $this;
    }

    public function prepareSort(string $sidx = '', string $sord = ''): self
    {
        $this->preparedBuilderGuard();
        if ($sidx === '') {
            return $this;
        }

        foreach (\explode(',', $sidx) as $currentSidx) {
            if (\preg_match('/(.+)\s(asc|desc)/', $currentSidx, $sidxMatch)) {
                $sidxMatch[1] = \trim($sidxMatch[1]);
                $this->builder->addOrderBy(
                    self::ENTITY_ALIAS . '.' . $sidxMatch[1],
                    $sidxMatch[2] === 'asc' ? 'ASC' : 'DESC'
                );
            } else {
                $currentSidx = \trim($currentSidx);
                $this->builder->addOrderBy(
                    self::ENTITY_ALIAS . '.' . $currentSidx,
                    $sord === 'asc' ? 'ASC' : 'DESC'
                );
            }
        }

        return $this;
    }

    public function prepareSearch(array $filters = []): self
    {
        $this->preparedBuilderGuard();
        if (\count($filters) === 0) {
            return $this;
        }

        $baseCondition = 'andWhere';
        if ($filters['groupOp'] === 'OR') {
            $baseCondition = 'orWhere';
        } elseif ($filters['groupOp'] !== 'AND') {
            throw new \LogicException('Unsupported value in `groupOp` param');
        }

        $paramNum = 0;
        $this->addSearchOptionsRecursively($filters, $baseCondition, $paramNum);

        return $this;
    }

    public function getPaginator(int $firstResult, int $maxResults): Paginator
    {
        $this->preparedBuilderGuard();

        $this->builder
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return new Paginator($this->builder);
    }

    private function addSearchOptionsRecursively(array $filters, string $baseCondition, int &$paramNum): void
    {
        $ruleArray = [];
        $currentGroup = 'andX';
        if (isset($filters['groups'])) {
            foreach ($filters['groups'] as $group) {
                $this->addSearchOptionsRecursively($group, $baseCondition, $paramNum);
            }
        }

        if ($filters['groupOp'] === 'OR') {
            $currentGroup = 'orX';
        } elseif ($filters['groupOp'] !== 'AND') {
            throw new \LogicException('Unsupported value in `groupOp` param');
        }

        foreach ($filters['rules'] as $rule) {
            $rule['field'] = self::ENTITY_ALIAS . '.' . $rule['field'];

            // null value in filters
            if ($rule['op'] === 'eq' && $rule['data'] === self::NULL_FIELD_VALUE) {
                $rule['op'] = 'nu';
            }

            switch ($rule['op']) {
                case 'eq':
                    $ruleArray[] = $this->builder->expr()->eq($rule['field'], "?{$paramNum}");
                    break;
                case 'ne':
                    $ruleArray[] = $this->builder->expr()->neq($rule['field'], "?{$paramNum}");
                    break;
                case 'bw':
                    $ruleArray[] = $this->builder->expr()->like($rule['field'], "?{$paramNum}");
                    $rule['data'] = "{$rule['data']}%";
                    break;
                case 'bn':
                    $ruleArray[] = $this->builder->expr()->notLike($rule['field'], "?{$paramNum}");
                    $rule['data'] = "{$rule['data']}%";
                    break;
                case 'ew':
                    $ruleArray[] = $this->builder->expr()->like($rule['field'], "?{$paramNum}");
                    $rule['data'] = "%{$rule['data']}";
                    break;
                case 'en':
                    $ruleArray[] = $this->builder->expr()->notLike($rule['field'], "?{$paramNum}");
                    $rule['data'] = "%{$rule['data']}";
                    break;
                case 'cn':
                    $ruleArray[] = $this->builder->expr()->like($rule['field'], "?{$paramNum}");
                    $rule['data'] = "%{$rule['data']}%";
                    break;
                case 'nc':
                    $ruleArray[] = $this->builder->expr()->notLike($rule['field'], "?{$paramNum}");
                    $rule['data'] = "%{$rule['data']}%";
                    break;
                case 'nu':
                    $ruleArray[] = $this->builder->expr()->isNull($rule['field']);
                    break;
                case 'nn':
                    $ruleArray[] = $this->builder->expr()->isNotNull($rule['field']);
                    break;
                case 'in':
                    $rule['data'] = \explode(',', $rule['data']);
                    \array_walk($rule['data'], 'trim');
                    $ruleArray[] = $this->builder->expr()->in($rule['field'], "?{$paramNum}");
                    break;
                case 'ni':
                    $rule['data'] = \explode(',', $rule['data']);
                    \array_walk($rule['data'], 'trim');
                    $ruleArray[] = $this->builder->expr()->notIn($rule['field'], "?{$paramNum}");
                    break;
                case 'lt':
                    $ruleArray[] = $this->builder->expr()->lt($rule['field'], "?{$paramNum}");
                    break;
                case 'le':
                    $ruleArray[] = $this->builder->expr()->lte($rule['field'], "?{$paramNum}");
                    break;
                case 'gt':
                    $ruleArray[] = $this->builder->expr()->gt($rule['field'], "?{$paramNum}");
                    break;
                case 'ge':
                    $ruleArray[] = $this->builder->expr()->gte($rule['field'], "?{$paramNum}");
                    break;
                default:
                    throw new \LogicException('Unsupported value in `op` or `searchOper` param');
            }
            if ($rule['op'] !== 'nu' && $rule['op'] !== 'nn') {
                $this->builder->setParameter($paramNum, $rule['data']);
            }

            ++$paramNum;
        }
        if (\count($ruleArray)) {
            $this->builder->$baseCondition(
                \call_user_func_array([$this->builder->expr(), $currentGroup], $ruleArray)
            );
        }
    }

    protected function preparedBuilderGuard(): void
    {
        if ($this->builder === null) {
            throw new \LogicException('Call `preparedBuilder` method first. ');
        }
    }
}
