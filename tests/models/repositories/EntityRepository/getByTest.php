<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 *
 * @runTestsInSeparateProcesses
 */
class getByTest extends BaseEntityRepository
{
    private $entity;

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
     * @desccription Test case for handling invalid string format for $key_name
     *
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key_name
     */
    public function testGetByWithInvalidKeyName($key_name)
    {
        $value = 'value';
        $this->assertEquals([], $this->repository->getBy($key_name, $value));
    }

    /**
     * @desccription Test case for handling invalid integer format for $value
     *
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $value
     */
    public function testGetByWithInvalidvalue($value)
    {
        $key_name = 'key';
        $this->assertEquals([], $this->repository->getBy($key_name, $value));
    }

    /**
     * @desccription Test case for handling scenario
     * when entity object cannot be retrieved.
     */
    public function testWhenEntityObjectCantBeRetrieved()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive('getEntityObject')
            ->with('EntityObject')
            ->andReturn(null);
        $this->assertEquals([], $this->repository->getBy('key', 'value'));
    }

    /**
     * @desccription  Test case for handling scenario
     * when entity definition is not an array.
     */
    public function testWhenEntityDefinitionIsNotArray()
    {
        $this->repository->entity_name = 'EntityObject';
        $entity = \Mockery::mock('EntityObject');
        $entity->shouldReceive('getDefinition')
            ->andReturn(null);

        $this->repository->shouldReceive('getEntityObject')
            ->with('EntityObject')
            ->andReturn($entity);

        $this->assertEquals([], $this->repository->getBy('key', 'value'));
    }

    /**
     * @desccription Test case for handling scenario
     * when entity definition does not have 'table'.
     */
    public function testWhenEntityDefinitionHasNoTable()
    {
        $this->repository->entity_name = 'EntityObject';
        $entity = \Mockery::mock('EntityObject');
        $entity->shouldReceive('getDefinition')
            ->andReturn([
                'primary' => 'primary',
            ]);

        $this->repository->shouldReceive('getEntityObject')
            ->with('EntityObject')
            ->andReturn($entity);

        $this->assertEquals([], $this->repository->getBy('key', 'value'));
    }

    /**
     * @desccription Test case for retrieving entity datas
     * when the entity object and definition are valid.
     */
    public function testWhenEntityObjectAndDefinitionAreValid()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive('getEntityObject')
            ->with('EntityObject')
            ->andReturn($this->entity);

        $this->repository->shouldReceive('select')
            ->andReturn($this->repository);

        $this->repository->shouldReceive('fields')
            ->andReturn($this->repository);

        $this->repository->shouldReceive('from')
            ->with('table')
            ->andReturn($this->repository);

        $this->repository->shouldReceive('where')
            ->with("`key` = 'value'")
            ->andReturn($this->repository);

        $this->repository->shouldReceive('build')
            ->with('unique_value')
            ->andReturn(['entity_data']);

        $expected_result = ['entity_data'];

        $this->assertEquals($expected_result, $this->repository->getBy('key', 'value'));
    }
}
