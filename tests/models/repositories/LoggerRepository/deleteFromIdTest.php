<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @runTestsInSeparateProcesses
 */
class deleteFromIdTest extends BaseLoggerRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_logger
     */
    public function testWhenGivenIdLoggerIsInvalidIntegerFormat($id_logger)
    {
        $this->assertFalse($this->repository->deleteFromId($id_logger));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $id_logger = 42;
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->deleteFromId($id_logger));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $id_logger = 42;
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->deleteFromId($id_logger));
    }
}
