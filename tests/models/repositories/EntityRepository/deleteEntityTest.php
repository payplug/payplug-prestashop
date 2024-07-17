<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 *
 * @runTestsInSeparateProcesses
 */
class deleteEntityTest extends BaseEntityRepository
{
    private $entity;
    private $entity_id;

    public function setUp()
    {
        parent::setUp();
        $this->entity_id = 42;
        $this->entity = \Mockery::mock('EntityObject');
        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'table',
                'primary' => 'primary',
            ],
        ]);
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $entity_id
     */
    public function testWhenGivenIdIsInvalidIntegerFormat($entity_id)
    {
        $this->assertFalse($this->repository->deleteEntity($entity_id));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->assertFalse($this->repository->deleteEntity($this->entity_id));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->deleteEntity($this->entity_id));
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

        $this->assertFalse($this->repository->deleteEntity($this->entity_id));
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

        $this->assertTrue($this->repository->deleteEntity($this->entity_id));
    }
}
