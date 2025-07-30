<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 */
class deleteByTest extends BaseEntityRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_key
     */
    public function testWhenGivenKeyIsInvalidIntegerFormat($entity_key)
    {
        $this->assertFalse($this->repository->deleteBy($entity_key, $this->entity_value));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_value
     */
    public function testWhenGivenValueIsInvalidIntegerFormat($entity_value)
    {
        $this->assertFalse($this->repository->deleteBy($this->entity_key, $entity_value));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->assertFalse($this->repository->deleteBy($this->entity_key, $this->entity_value));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->deleteBy($this->entity_key, $this->entity_value));
    }

    public function testWhenEntityObjectCantBeDeleted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertFalse($this->repository->deleteBy($this->entity_key, $this->entity_value));
    }

    public function testWhenEntityObjectIsDeleted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertTrue($this->repository->deleteBy($this->entity_key, $this->entity_value));
    }
}
