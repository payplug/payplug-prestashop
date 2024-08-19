<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 *
 * @runTestsInSeparateProcesses
 */
class getEntityTest extends BaseEntityRepository
{
    /**
     * Test case for handling invalid integer format for $entity_id.
     *
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $entity_id
     */
    public function testWhenGivenIdIsInvalidIntegerFormat($entity_id)
    {
        $this->assertEquals([], $this->repository->getEntity($entity_id));
    }

    /**
     * Test case for handling scenario when no entity name is defined.
     */
    public function testWhenNoEntityNameDefined()
    {
        $this->assertEquals([], $this->repository->getEntity($this->entity_id));
    }

    /**
     * Test case for handling scenario when entity object cannot be retrieved.
     */
    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->entity_name = 'EntityObject';
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertEquals([], $this->repository->getEntity($this->entity_id));
    }

    /**
     * Test case for handling scenario when entity object is not found.
     */
    public function testWhenEntityObjectCantBeFound()
    {
        $this->repository->entity_name = 'EntityObject';
        // Mock getEntityObject to return null instead of $this->entity
        $this->repository->shouldReceive('getEntityObject')
            ->with('EntityObject')
            ->andReturn(null);

        // other methods are not called because getEntityObject returns null
        $this->repository->shouldNotReceive(['select', 'fields', 'from', 'where', 'build']);

        $this->assertEquals([], $this->repository->getEntity($this->entity_id));
    }

    /**
     *  Test case for retrieving entity data when the entity object is found.
     */
    public function testWhenEntityObjectIsFound()
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
            ->with('table_name')
            ->andReturn($this->repository);
        $this->repository->shouldReceive('where')
            ->with('`id`=' . (int) $this->entity_id)
            ->andReturn($this->repository);

        $this->repository->shouldReceive('build')
            ->with('unique_value')
            ->andReturn(['entity_data']);

        $expected_result = ['entity_data'];

        $this->assertEquals($expected_result, $this->repository->getEntity($this->entity_id));
    }
}
