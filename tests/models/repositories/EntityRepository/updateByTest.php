<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 *
 * @runTestsInSeparateProcesses
 */
class updateByTest extends BaseEntityRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_key
     */
    public function testWhenGivenKeyIsInvalidIntegerFormat($entity_key)
    {
        $this->assertFalse($this->repository->updateBy($entity_key, $this->entity_value, $this->entity_fields));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_value
     */
    public function testWhenGivenValueIsInvalidIntegerFormat($entity_value)
    {
        $this->assertFalse($this->repository->updateBy($this->entity_key, $entity_value, $this->entity_fields));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $entity_fields
     */
    public function testWhenGivenFieldsIsInvalidArrayFormat($entity_fields)
    {
        $this->assertFalse($this->repository->updateBy($this->entity_key, $this->entity_value, $entity_fields));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->assertFalse($this->repository->updateBy($this->entity_key, $this->entity_value, $this->entity_fields));
    }

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->updateBy($this->entity_key, $this->entity_value, $this->entity_fields));
    }

    public function testWhenEntityObjectCantBeUpdated()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'update' => $this->repository,
            'table' => $this->repository,
            'set' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertFalse($this->repository->updateBy($this->entity_key, $this->entity_value, $this->entity_fields));
    }

    public function testWhenEntityObjectIsUpdated()
    {
        $this->repository->entity_name = 'EntityObject';

        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'update' => $this->repository,
            'table' => $this->repository,
            'set' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertTrue($this->repository->updateBy($this->entity_key, $this->entity_value, $this->entity_fields));
    }
}
