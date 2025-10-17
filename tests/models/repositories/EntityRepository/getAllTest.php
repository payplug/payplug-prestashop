<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 *
 * @runTestsInSeparateProcesses
 */
class getAllTest extends BaseEntityRepository
{
    public function testWhenNoEntityNameDefined()
    {
        $this->assertSame([], $this->repository->getAll());
    }

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertSame([], $this->repository->getAll());
    }

    public function testWhenEntityCollectionCantBeGot()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame([], $this->repository->getAll());
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
            'build' => $collection,
        ]);

        $this->assertSame($collection, $this->repository->getAll());
    }
}
