<?php

namespace himiklab\JqGridBundle\Tests\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use himiklab\JqGridBundle\Tests\Entity;
use himiklab\JqGridBundle\Util\EntityHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityHandlerTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $classMetadata;

    /** @var Entity */
    private $entity;

    /** @var EntityHandler */
    private $handler;

    public function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($this->classMetadata);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->entity = new Entity();
        $this->entity->nestedEntity = new Entity();

        $this->handler = new EntityHandler($this->entityManager, $this->validator);
    }

    public function testGetByAttribute()
    {
        $this->entity->attributePublic = 'test';
        $this->assertEquals('test', $this->handler->getValue($this->entity, 'attributePublic'));
    }

    public function testGetByGetter()
    {
        $this->entity->setAttributePrivate('test');
        $this->assertEquals('test', $this->handler->getValue($this->entity, 'attributePrivate'));
    }

    public function testGetDateTime()
    {
        $this->entity->attributePublic = new \DateTime('2000-01-01 00:00:00');
        $this->assertEquals('2000-01-01 00:00:00', $this->handler->getValue($this->entity, 'attributePublic'));
    }

    public function testGetNestedAttribute()
    {
        $this->entity->nestedEntity->attributePublic = 'test';
        $this->assertEquals('test', $this->handler->getValue($this->entity, 'nestedEntity.attributePublic'));
    }

    public function testSetByAttribute()
    {
        $this->handler->setValue($this->entity, 'attributePublic', 'test');
        $this->assertEquals('test', $this->entity->attributePublic);
    }

    public function testSetBySetter()
    {
        $this->handler->setValue($this->entity, 'attributePrivate', 'test');
        $this->assertEquals('test', $this->entity->getAttributePrivate());
    }

    public function testSetDateTime()
    {
        $this->classMetadata
            ->expects($this->once())
            ->method('getTypeOfField')
            ->willReturn('date');

        $this->handler->setValue($this->entity, 'attributePublic', '2000-01-01 00:00:00');
        $this->assertInstanceOf(\DateTime::class, $this->entity->attributePublic);
        $this->assertEquals('2000-01-01 00:00:00', $this->entity->attributePublic->format('Y-m-d H:i:s'));
    }

    public function testSetNestedAttribute()
    {
        $this->handler->setValue($this->entity, 'nestedEntity.attributePublic', 'test');
        $this->assertEquals('test', $this->entity->nestedEntity->attributePublic);
    }

    public function testSetEmbeddedClass()
    {
        $this->classMetadata->embeddedClasses['attributePublic']['class'] = Entity::class;

        $this->handler->setValue($this->entity, 'attributePublic.attributePublic', 'test');
        $this->assertInstanceOf(Entity::class, $this->entity->attributePublic);
        $this->assertEquals('test', $this->entity->attributePublic->attributePublic);
    }

    public function testValidatePassed()
    {
        $this->validateInitialization();
        $this->assertEquals(null, $this->handler->validate($this->entity));
    }

    public function testValidateFiled()
    {
        $this->validateInitialization();
        $this->assertEquals('test' . PHP_EOL, $this->handler->validate(new EntityHandlerTestEntityInvalid()));
    }

    public function testConvertEntityToGrid()
    {
        $this->classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['attributePublic', 'attributePrivate']);
        $this->entity->attributePublic = 'test1';
        $this->entity->setAttributePrivate('test2');

        $this->assertEquals('test1%test2', $this->handler->convertEntityIdToGrid($this->entity));
    }

    public function testConvertGridToEntity()
    {
        $this->classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['attributePublic', 'attributePrivate']);

        $this->assertEquals(
            ['attributePublic' => 'test1', 'attributePrivate' => 'test2'],
            $this->handler->convertGridIdToEntity(Entity::class, 'test1%test2')
        );
    }

    public function testConvertGridToEntityNumberException()
    {
        $this->classMetadata
            ->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['attributePublic', 'attributePrivate']);

        $this->expectException(\LogicException::class);
        $this->handler->convertGridIdToEntity(Entity::class, 'test1');
    }

    public function testGetFields()
    {
        $this->classMetadata
            ->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['attributePublic', 'attributePrivate']);

        $this->assertEquals(
            ['attributePublic', 'attributePrivate'],
            $this->handler->getFields(Entity::class)
        );
    }

    private function validateInitialization()
    {
        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturnCallback(function ($entity) {
                if ($entity instanceof Entity) {
                    return [];
                }

                $constraint = $this->createMock(ConstraintViolationInterface::class);
                $constraint
                    ->expects($this->once())
                    ->method('getMessage')
                    ->willReturn('test');
                return [$constraint];
            });
    }
}

class EntityHandlerTestEntityInvalid
{
}
