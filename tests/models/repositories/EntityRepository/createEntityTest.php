<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 *
 * @runTestsInSeparateProcesses
 */
class createEntityTest extends BaseEntityRepository
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
                    'key_1' => ['type' => 'string', 'required' => true],
                    'key_2' => ['type' => 'integer'],
                    'key_3' => ['type' => 'boolean'],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $entity_fields
     */
    public function testWhenGivenFieldsIsInvalidArrayFormat($entity_fields)
    {
        $this->assertSame(0, $this->repository->createEntity($entity_fields));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->assertSame(0, $this->repository->createEntity($this->entity_fields));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertSame(0, $this->repository->createEntity($this->entity_fields));
    }

    public function testWhenRequiredFieldIsNotGiven()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'insert' => $this->repository,
            'into' => $this->repository,
        ]);

        $entity_fields = [
            'key_2' => 42,
            'key_3' => true,
        ];

        $this->assertSame(0, $this->repository->createEntity($entity_fields));
    }

    public function testWhenRequiredFieldIsinvalidFormat()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'insert' => $this->repository,
            'into' => $this->repository,
        ]);

        $entity_fields = [
            'key_1' => 42,
            'key_2' => 42,
            'key_3' => true,
        ];

        $this->assertSame(0, $this->repository->createEntity($entity_fields));
    }

    public function testWhenEntityObjectCantBeCreated()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'insert' => $this->repository,
            'into' => $this->repository,
            'fields' => $this->repository,
            'values' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(0, $this->repository->createEntity($this->entity_fields));
    }

    public function testWhenEntityObjectIsCreated()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'update' => $this->repository,
            'table' => $this->repository,
            'set' => $this->repository,
            'where' => $this->repository,
            'lastId' => $this->entity_id,
            'build' => true,
        ]);

        $this->assertSame($this->entity_id, $this->repository->createEntity($this->entity_fields));
    }
}
