<?php
/**
 * @link https://github.com/himiklab/jqgrid-bundle
 * @copyright Copyright (c) 2018-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\JqGridBundle\Util;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionMethod;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityHandler
{
    private const COMPOSITE_KEY_DELIMITER = '%';
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /** @var ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ClassMetadata[] */
    private $classMetadataCache = [];

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function getValue(Object $entity, string $field)
    {
        $result = $entity;
        foreach (\explode('.', $field) as $fieldPart) {
            $getterName = 'get' . \ucfirst($fieldPart);

            if (\method_exists($result, $getterName)) {
                $result = $result->$getterName();
            } else {
                $result = $result->{$fieldPart};
            }

            if (\is_object($result) && $result instanceof \DateTimeInterface) {
                $result = $result->format(self::DATE_TIME_FORMAT);
            }
        }

        return $result;
    }

    public function setValue(Object $entity, string $field, $value): void
    {
        $currentEntity = $entity;
        $fieldParts = \explode('.', $field);
        $fieldPartsCount = \count($fieldParts);

        for ($i = 0; $i < $fieldPartsCount - 1; ++$i) {
            $currentValue = $this->getValue($currentEntity, $fieldParts[$i]);
            if (!\is_object($currentValue)) {
                $currentEmbedded = $this->getEmbedded($fieldParts[$i], \get_class($currentEntity));
                $this->setValue($currentEntity, $fieldParts[$i], $currentEmbedded);
                $currentEntity = $currentEmbedded;
            } else {
                $currentEntity = $currentValue;
            }
        }

        if ($this->isFieldDateTime(\get_class($entity), $field)) {
            $value = new \DateTime($value);
        }
        $setterName = 'set' . \ucfirst(\end($fieldParts));
        if (\method_exists($currentEntity, $setterName)) {
            $currentEntity->$setterName($value);
        } else {
            $currentEntity->{\end($fieldParts)} = $value;
        }
    }

    public function validate(Object $entity): ?string
    {
        $errors = $this->validator->validate($entity);
        if (\count($errors) === 0) {
            return null;
        }

        $result = '';
        foreach ($errors as $currentError) {
            $result .= ($currentError->getMessage() . PHP_EOL);
        }

        return $result;
    }

    public function convertEntityIdToGrid(Object $entity): string
    {
        $identifierFieldNames = $this->getClassMetadata(\get_class($entity))->getIdentifierFieldNames();

        $identifiers = [];
        foreach ($identifierFieldNames as $currentIdentifierFieldName) {
            $identifiers[] = $this->getValue($entity, $currentIdentifierFieldName);
        }
        return \implode(self::COMPOSITE_KEY_DELIMITER, $identifiers);
    }

    public function convertGridIdToEntity(string $entityName, string $id): array
    {
        $identifierFieldNames = $this->getClassMetadata($entityName)->getIdentifierFieldNames();

        $idParts = \explode(self::COMPOSITE_KEY_DELIMITER, $id);
        if (\count($identifierFieldNames) !== \count($idParts)) {
            throw new \LogicException('The number of composite IDs required and sent is different.');
        }

        return \array_combine($identifierFieldNames, $idParts);
    }

    public function getFields(string $entityName): array
    {
        return $this->entityManager->getClassMetadata($entityName)->getFieldNames();
    }

    private function isFieldDateTime(string $entityName, string $field): bool
    {
        $fieldType = $this->getClassMetadata($entityName)->getTypeOfField($field);
        return $fieldType === 'date' ||
            $fieldType === 'time' ||
            $fieldType === 'datetime' ||
            $fieldType === 'datetimetz';
    }

    private function getEmbedded(string $field, string $entityName): Object
    {
        $embeddedClassName = $this->getClassMetadata($entityName)->embeddedClasses[$field]['class'] ?? null;

        if (!\class_exists($embeddedClassName)) {
            throw new \LogicException('The embedded entity class isn\'t exists.');
        }
        if (\method_exists($embeddedClassName, '__construct') &&
            (new ReflectionMethod($embeddedClassName, '__construct'))->getNumberOfRequiredParameters() !== 0
        ) {
            throw new \LogicException('The embedded entity has constructor with required params.');
        }

        return new $embeddedClassName;
    }

    private function getClassMetadata(string $entityName): ClassMetadata
    {
        if (isset($this->classMetadataCache[$entityName])) {
            return $this->classMetadataCache[$entityName];
        }

        return $this->classMetadataCache[$entityName] = $this->entityManager->getClassMetadata($entityName);
    }
}
