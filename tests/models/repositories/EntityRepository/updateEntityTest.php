<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 *
 * @runTestsInSeparateProcesses
 */
class updateEntityTest extends BaseEntityRepository
{
    private $entity;
    private $entity_id;

    public function setUp()
    {
        parent::setUp();
        $this->entity_id = 42;
        $this->entity_fields = [
            'key_1' => 'value_1',
            'key_2' => 42,
            'key_3' => true,
        ];
        $this->entity = \Mockery::mock('EntityObject');
        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'table',
                'primary' => 'primary',
                'fields' => [
                    'key_1' => ['type' => 'string'],
                    'key_2' => ['type' => 'integer'],
                    'key_3' => ['type' => 'boolean'],
                ],
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
        $this->assertFalse($this->repository->updateEntity($entity_id, $this->entity_fields));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $entity_fields
     */
    public function testWhenGivenFieldsIsInvalidArrayFormat($entity_fields)
    {
        $this->assertFalse($this->repository->updateEntity($this->entity_id, $entity_fields));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->assertFalse($this->repository->updateEntity($this->entity_id, $this->entity_fields));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->updateEntity($this->entity_id, $this->entity_fields));
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

        $this->assertFalse($this->repository->updateEntity($this->entity_id, $this->entity_fields));
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

        $this->assertTrue($this->repository->updateEntity($this->entity_id, $this->entity_fields));
    }
}
