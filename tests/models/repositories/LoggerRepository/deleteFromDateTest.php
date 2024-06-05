<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class deleteFromDateTest extends BaseLoggerRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date
     */
    public function testWhenGivenDateIsInvalidStringFormat($date)
    {
        $this->assertFalse($this->repository->deleteFromDate($date));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $date = date('Y-m-d H:i:s');
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->deleteFromDate($date));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $date = date('Y-m-d H:i:s');
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->deleteFromDate($date));
    }
}
