<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 */
class deleteFromIdTest extends BaseLoggerRepository
{
    private $last_id;

    public function setUp()
    {
        parent::setUp();
        $this->last_id = 42;
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $last_id
     */
    public function testWhenGivenIdLoggerIsInvalidIntegerFormat($last_id)
    {
        $this->assertFalse($this->repository->deleteFromId($last_id));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->repository->entity_name = '';
        $this->assertFalse($this->repository->deleteFromId($this->last_id));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->deleteFromId($this->last_id));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertFalse($this->repository->deleteFromId($this->last_id));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertTrue($this->repository->deleteFromId($this->last_id));
    }
}
