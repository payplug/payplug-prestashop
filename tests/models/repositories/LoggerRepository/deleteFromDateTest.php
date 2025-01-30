<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @runTestsInSeparateProcesses
 */
class deleteFromDateTest extends BaseLoggerRepository
{
    public $date;

    public function setUp()
    {
        parent::setUp();
        $this->date = date('Y-m-d H:i:s');
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date
     */
    public function testWhenGivenDateIsInvalidStringFormat($date)
    {
        $this->assertFalse($this->repository->deleteFromDate($date));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->repository->entity_name = '';
        $this->assertFalse($this->repository->deleteFromDate($this->date));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->deleteFromDate($this->date));
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

        $this->assertFalse($this->repository->deleteFromDate($this->date));
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

        $this->assertTrue($this->repository->deleteFromDate($this->date));
    }
}
