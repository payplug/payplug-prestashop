<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 */
class getAllByTest extends BaseEntityRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_key
     */
    public function testWhenGivenKeyIsInvalidIntegerFormat($entity_key)
    {
        $this->assertSame([], $this->repository->getAllBy($entity_key, $this->entity_value));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $entity_value
     */
    public function testWhenGivenValueIsInvalidIntegerFormat($entity_value)
    {
        $this->assertSame([], $this->repository->getAllBy($this->entity_key, $entity_value));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->assertSame([], $this->repository->getAllBy($this->entity_key, $this->entity_value));
    }

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertSame([], $this->repository->getAllBy($this->entity_key, $this->entity_value));
    }

    public function testWhenGivenKeyIsNotAllowed()
    {
        $this->repository->entity_name = 'EntityObject';
        $entity_key = 'wrong_key';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
        ]);
        $this->assertSame([], $this->repository->getAllBy($entity_key, $this->entity_value));
    }

    public function testWhenEntityCollectionCantBeGot()
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

        $this->assertSame([], $this->repository->getAllBy($this->entity_key, $this->entity_value));
    }

    public function testWhenEntityCollectionIsGot()
    {
        $this->repository->entity_name = 'EntityObject';
        $collection = [
            [
                'key' => 'value',
            ],
        ];
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $collection,
        ]);

        $this->assertSame($collection, $this->repository->getAllBy($this->entity_key, $this->entity_value));
    }
}
