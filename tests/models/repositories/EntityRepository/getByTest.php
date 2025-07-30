<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 */
class getByTest extends BaseEntityRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_key
     */
    public function testWhenGivenKeyIsInvalidIntegerFormat($entity_key)
    {
        $this->assertSame([], $this->repository->getBy($entity_key, $this->entity_value));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_value
     */
    public function testWhenGivenValueIsInvalidIntegerFormat($entity_value)
    {
        $this->assertSame([], $this->repository->getBy($this->entity_key, $entity_value));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->assertSame([], $this->repository->getBy($this->entity_key, $this->entity_value));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertSame([], $this->repository->getBy($this->entity_key, $this->entity_value));
    }

    public function testWhenGivenKeyIsNotAllowed()
    {
        $this->repository->entity_name = 'EntityObject';
        $entity_key = 'wrong_key';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
        ]);
        $this->assertSame([], $this->repository->getBy($entity_key, $this->entity_value));
    }

    public function testWhenEntityCantBeGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame([], $this->repository->getBy($this->entity_key, $this->entity_value));
    }

    public function testWhenEntityIsGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $entity = [
            'key' => 'value',
        ];
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $entity,
        ]);

        $this->assertSame($entity, $this->repository->getBy($this->entity_key, $this->entity_value));
    }
}
