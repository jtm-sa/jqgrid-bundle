<?php

namespace himiklab\JqGridBundle\Tests\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use himiklab\JqGridBundle\Tests\Entity;
use himiklab\JqGridBundle\Util\EntityFinder;
use PHPUnit\Framework\TestCase;

class EntityFinderTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $builder;

    /** @var EntityFinder */
    private $finder;

    public function setUp()
    {
        parent::setUp();

        $this->builder = $this->createMock(QueryBuilder::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->finder = new EntityFinder($this->entityManager);
    }

    public function testNotExistingEntity()
    {
        $this->expectException(\LogicException::class);
        $this->finder->prepareBuilder([], 'NotExistingEntity');
    }

    public function testPrepareBuilderWithoutColumns()
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->builder);
        $this->builder
            ->expects($this->once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $this->builder
            ->expects($this->once())
            ->method('from')
            ->with(Entity::class, 'e')
            ->willReturnSelf();
        $this->finder->prepareBuilder([], Entity::class);
    }

    public function testPrepareBuilderWithColumns()
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->builder);
        $this->builder
            ->expects($this->once())
            ->method('select')
            ->with('partial e.{param1,param2}')
            ->willReturnSelf();
        $this->builder
            ->expects($this->once())
            ->method('from')
            ->with(Entity::class, 'e')
            ->willReturnSelf();
        $this->finder->prepareBuilder(['param1', 'param2'], Entity::class);
    }

    public function testPrepareBuilderWithScope()
    {
        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->builder);
        $this->builder
            ->expects($this->once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $this->builder
            ->expects($this->once())
            ->method('from')
            ->with(Entity::class, 'e')
            ->willReturnSelf();

        $this->finder->prepareBuilder([], Entity::class, function ($builder) {
            $this->assertInstanceOf(QueryBuilder::class, $builder);
        });
    }

    public function testPrepareSort()
    {
        $this->injectBuilder();
        $this->builder
            ->expects($this->exactly(2))
            ->method('addOrderBy')
            ->withConsecutive(
                ['e.param1', 'ASC'],
                ['e.param2', 'DESC']
            )
            ->willReturnSelf();

        $this->finder->prepareSort('param1 asc,param2 desc');
    }

    public function testPrepareSortWithSord()
    {
        $this->injectBuilder();
        $this->builder
            ->expects($this->exactly(2))
            ->method('addOrderBy')
            ->withConsecutive(
                ['e.param1', 'ASC'],
                ['e.param2', 'ASC']
            )
            ->willReturnSelf();

        $this->finder->prepareSort('param1,param2', 'asc');
    }

    public function testPrepareSearch()
    {
        $this->injectBuilder();
        $expr = $this->createMock(Expr::class);
        $expr->expects($this->exactly(3))
            ->method('eq')
            ->withConsecutive(['e.fieldEq', '?0'], ['e.fieldEq', '?17'], ['e.fieldEq', '?18']);
        $expr->expects($this->once())
            ->method('neq')
            ->with('e.fieldNe', '?1');
        $expr->expects($this->exactly(3))
            ->method('like')
            ->withConsecutive(
                ['e.fieldBw', '?2'],
                ['e.fieldEw', '?3'],
                ['e.fieldCn', '?4']
            );
        $expr->expects($this->exactly(3))
            ->method('notLike')
            ->withConsecutive(
                ['e.fieldBn', '?5'],
                ['e.fieldEn', '?6'],
                ['e.fieldNc', '?7']
            );
        $expr->expects($this->once())
            ->method('lt')
            ->with('e.fieldLt', '?8');
        $expr->expects($this->once())
            ->method('lte')
            ->with('e.fieldLe', '?9');
        $expr->expects($this->once())
            ->method('gt')
            ->with('e.fieldGt', '?10');
        $expr->expects($this->once())
            ->method('gte')
            ->with('e.fieldGe', '?11');
        $expr->expects($this->once())
            ->method('in')
            ->with('e.fieldIn', '?12');
        $expr->expects($this->once())
            ->method('notIn')
            ->with('e.fieldNi', '?13');
        $expr->expects($this->exactly(2))
            ->method('isNull')
            ->withConsecutive(['e.fieldNu'], ['e.fieldEqNull']);
        $expr->expects($this->once())
            ->method('isNotNull')
            ->with('e.fieldNn');
        $expr->expects($this->exactly(2))
            ->method('andX');

        $this->builder
            ->expects($this->exactly(3))
            ->method('expr')
            ->willReturn($expr);
        $this->builder
            ->expects($this->exactly(16))
            ->method('setParameter')
            ->withConsecutive(
                [0, 'testEq', null],
                [1, 'testNe', null],
                [2, 'testBw%', null],
                [3, '%testEw', null],
                [4, '%testCn%', null],
                [5, 'testBn%', null],
                [6, '%testEn', null],
                [7, '%testNc%', null],
                [8, 'testLt', null],
                [9, 'testLe', null],
                [10, 'testGt', null],
                [11, 'testGe', null],
                [12, ['test1', 'test2'], null],
                [13, ['test1', 'test2'], null],
                [17, 'testEq1', null],
                [18, 'testEq2', null]
            );
        $this->builder
            ->expects($this->exactly(2))
            ->method('orWhere');

        $this->finder->prepareSearch([
            'groupOp' => 'OR',
            'rules' => [],
            'groups' => [
                [
                    'groupOp' => 'AND',
                    'rules' => [
                        ['field' => 'fieldEq', 'op' => 'eq', 'data' => 'testEq',],
                        ['field' => 'fieldNe', 'op' => 'ne', 'data' => 'testNe',],
                        ['field' => 'fieldBw', 'op' => 'bw', 'data' => 'testBw',],
                        ['field' => 'fieldEw', 'op' => 'ew', 'data' => 'testEw',],
                        ['field' => 'fieldCn', 'op' => 'cn', 'data' => 'testCn',],
                        ['field' => 'fieldBn', 'op' => 'bn', 'data' => 'testBn',],
                        ['field' => 'fieldEn', 'op' => 'en', 'data' => 'testEn',],
                        ['field' => 'fieldNc', 'op' => 'nc', 'data' => 'testNc',],
                        ['field' => 'fieldLt', 'op' => 'lt', 'data' => 'testLt',],
                        ['field' => 'fieldLe', 'op' => 'le', 'data' => 'testLe',],
                        ['field' => 'fieldGt', 'op' => 'gt', 'data' => 'testGt',],
                        ['field' => 'fieldGe', 'op' => 'ge', 'data' => 'testGe',],
                        ['field' => 'fieldIn', 'op' => 'in', 'data' => 'test1,test2',],
                        ['field' => 'fieldNi', 'op' => 'ni', 'data' => 'test1,test2',],
                        ['field' => 'fieldNu', 'op' => 'nu',],
                        ['field' => 'fieldNn', 'op' => 'nn',],
                        ['field' => 'fieldEqNull', 'op' => 'eq', 'data' => '_null',],
                    ],
                ],
                [
                    'groupOp' => 'AND',
                    'rules' => [
                        ['field' => 'fieldEq', 'op' => 'eq', 'data' => 'testEq1',],
                        ['field' => 'fieldEq', 'op' => 'eq', 'data' => 'testEq2',],
                    ],
                ],
            ],
        ]);
    }

    public function testInvalidGroupOp1()
    {
        $this->injectBuilder();
        $this->expectException(\LogicException::class);
        $this->finder->prepareSearch([
            'groupOp' => 'test',
            'rules' => [],
        ]);
    }

    public function testInvalidGroupOp2()
    {
        $this->injectBuilder();
        $this->expectException(\LogicException::class);
        $this->finder->prepareSearch([
            'groupOp' => 'AND',
            'rules' => [],
            'groups' => [
                [
                    'groupOp' => 'test',
                    'rules' => [
                        ['field' => 'fieldEq', 'op' => 'eq', 'data' => 'testEq1',],
                    ],
                ],
            ],
        ]);
    }

    public function testInvalidSearchOper()
    {
        $this->injectBuilder();
        $this->expectException(\LogicException::class);
        $this->finder->prepareSearch([
            'groupOp' => 'AND',
            'rules' => [
                ['field' => 'fieldEq', 'op' => 'test', 'data' => 'testEq1',],
            ],
        ]);
    }

    public function testGetPaginator()
    {
        $this->injectBuilder();
        $this->builder
            ->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();
        $this->builder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $this->assertInstanceOf(Paginator::class, $this->finder->getPaginator(0, 10));
    }

    public function testBuilderGuardSort()
    {
        $this->expectException(\LogicException::class);
        $this->finder->prepareSort();
    }

    public function testBuilderGuardSearch()
    {
        $this->expectException(\LogicException::class);
        $this->finder->prepareSearch();
    }

    private function injectBuilder()
    {
        $finderReflection = new \ReflectionClass($this->finder);
        $builderProperty = $finderReflection->getProperty('builder');
        $builderProperty->setAccessible(true);
        $builderProperty->setValue($this->finder, $this->builder);
    }
}
