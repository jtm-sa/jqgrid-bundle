<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle;

use Doctrine\ORM\EntityManagerInterface;
use himiklab\JqGridBundle\Exception\EntityNotFoundException;
use himiklab\JqGridBundle\Util\EntityFinder;
use himiklab\JqGridBundle\Util\EntityHandler;
use himiklab\JqGridBundle\Util\RequestHandler;
use himiklab\JqGridBundle\Util\SearchFiltersDecoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JqGrid
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RequestHandler */
    private $requestHandler;

    /** @var EntityFinder */
    private $entityFinder;

    /** @var EntityHandler */
    private $entityHandler;

    /** @var SearchFiltersDecoder */
    private $searchFiltersDecoder;

    /** @var string */
    private $entityName;

    /** @var array */
    private $entityFields = [];

    /** @var callable */
    private $scope;

    public function __construct(EntityManagerInterface $entityManager, RequestHandler $requestHandler,
                                EntityFinder $entityFinder, EntityHandler $entityHandler,
                                SearchFiltersDecoder $searchFiltersDecoder)
    {
        $this->entityManager = $entityManager;
        $this->requestHandler = $requestHandler;
        $this->entityFinder = $entityFinder;
        $this->entityHandler = $entityHandler;
        $this->searchFiltersDecoder = $searchFiltersDecoder;
    }

    public function handleRead(Request $request): Response
    {
        $requestData = $this->requestHandler->getRequestData($request);

        $searchData = [];
        if ($requestData['_search'] === 'true') {
            $searchData = ['groupOp' => 'AND'];
            if ($requestData['filters'] !== '') {
                // advanced searching
                $searchData = $this->searchFiltersDecoder->decode($requestData['filters']);
            } else {
                // single searching
                $searchData['rules'][] = [
                    'op' => $requestData['searchOper'],
                    'field' => $requestData['searchField'],
                    'data' => $requestData['searchString']
                ];
            }
        }
        //var_dump($searchData);

        $paginator = $this->entityFinder
            ->prepareBuilder($this->entityFields, $this->entityName, $this->scope)
            ->prepareSearch($searchData)
            ->prepareSort($requestData['sidx'] ?? '', $requestData['sord'] ?? '')
            ->getPaginator(($requestData['page'] - 1) * $requestData['rows'], (int)$requestData['rows']);
        $recordsTotalCount = $paginator->count();

        $responseData = [];
        $responseData['page'] = $requestData['page'];
        $responseData['total'] = $requestData['rows'] ? \ceil($recordsTotalCount / $requestData['rows']) : 0;
        $responseData['records'] = $recordsTotalCount;

        $i = 0;
        foreach ($paginator as $entity) {

            $responseData['rows'][$i]['id'] = $this->entityHandler->convertEntityIdToGrid($entity);
            foreach ($this->entityFields as $modelAttribute) {
               // var_dump($modelAttribute);
                if(strpos($modelAttribute,'id.')){
                $responseData['rows'][$i]['cell'][$modelAttribute] = $this->entityHandler->convertEntityIdToGrid($entity);
                }else{
                    $responseData['rows'][$i]['cell'][$modelAttribute] = $this->entityHandler->getValue($entity, $modelAttribute);
                }

                
            }

            ++$i;
        }
        return new JsonResponse($responseData);
    }

    public function handleCreate(Request $request): ?Response
    {
        $requestData = $this->requestHandler->getRequestData($request);
        if (!isset($requestData['id'])) {
            throw new \LogicException('Id param isn\'t set.');
        }

        $entity = new $this->entityName;

        foreach ($this->entityFields as $column) {
            if ($column === 'id' || !isset($requestData[$column])) {
                continue;
            }
            
            $this->entityHandler->setValue($entity, $column, $requestData[$column]);
        }

        if (($errors = $this->entityHandler->validate($entity)) !== null) {

            return new Response($errors);
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return null;
    }

    public function handleUpdate(Request $request): ?Response
    {
        $requestData = $this->requestHandler->getRequestData($request);
        if (!isset($requestData['id'])) {
            throw new \LogicException('Id param isn\'t set.');
        }

        $entity = $this->entityManager->find(
            $this->entityName,
            $this->entityHandler->convertGridIdToEntity($this->entityName, $requestData['id'])
        );
        if ($entity === null) {
            throw new EntityNotFoundException('Entity isn\'t found by the ID.');
        }

        foreach ($this->entityFields as $column) {
            if ($column === 'id' || !isset($requestData[$column])) {
                continue;
            }

            $this->entityHandler->setValue($entity, $column, $requestData[$column]);
        }

        if (($errors = $this->entityHandler->validate($entity)) !== null) {
            return new Response($errors);
        }
        $this->entityManager->flush();

        return null;
    }

    public function handleDelete(Request $request): void
    {
        $requestData = $this->requestHandler->getRequestData($request);
        if (!isset($requestData['id'])) {
            throw new \LogicException('Id param isn\'t set.');
        }

        $deleteIds = \explode(',', $requestData['id']);
        foreach ($deleteIds as $currentId) {
            $entity = $this->entityManager->find(
                $this->entityName,
                $this->entityHandler->convertGridIdToEntity($this->entityName, $currentId)
            );
            if ($entity === null) {
                throw new EntityNotFoundException('Entity isn\'t found by the ID.');
            }

            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();
    }

    public function setEntityName(string $entityName): self
    {
        $this->entityName = $entityName;
        if (\count($this->entityFields) === 0) {
            $this->entityFields = $this->entityHandler->getFields($this->entityName);
        }

        return $this;
    }

    public function setEntityFields(array $entityFields): self
    {
        $this->entityFields = $entityFields;
        return $this;
    }

    public function setScope(callable $scope): self
    {
        $this->scope = $scope;
        return $this;
    }
}
