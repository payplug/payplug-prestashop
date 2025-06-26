<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 */
class deleteAllTest extends BaseEntityRepository
{
    public function testWhenNoEntityNameDefined()
    {
        $this->assertFalse($this->repository->deleteAll());
    }

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->deleteAll());
    }

    public function testWhenEntityObjectCantBeDeleted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'truncate' => $this->repository,
            'table' => $this->repository,
            'build' => false,
        ]);

        $this->assertFalse($this->repository->deleteAll());
    }

    public function testWhenEntityObjectIsDeleted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'truncate' => $this->repository,
            'table' => $this->repository,
            'build' => true,
        ]);

        $this->assertTrue($this->repository->deleteAll());
    }
}
