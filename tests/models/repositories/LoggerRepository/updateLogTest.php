<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class updateLogTest extends BaseLoggerRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_logger
     */
    public function testWhenGivenIdPayplugLoggerIsInvalidIntegerFormat($id_logger)
    {
        $parameters = [];
        $this->assertFalse($this->repository->updateLog($id_logger, $parameters));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $id_logger = 42;
        $this->assertFalse($this->repository->updateLog($id_logger, $parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $id_logger = 42;
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'update' => $this->repository,
                'table' => $this->repository,
                'set' => $this->repository,
                'where' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->updateLog($id_logger, $parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $id_logger = 42;
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'update' => $this->repository,
                'table' => $this->repository,
                'set' => $this->repository,
                'where' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->updateLog($id_logger, $parameters));
    }
}
