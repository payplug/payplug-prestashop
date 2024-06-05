<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class createLogTest extends BaseLoggerRepository
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $this->assertFalse($this->repository->createLog($parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->createLog($parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $id_logger = 42;
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'lastId' => $id_logger,
                'build' => true,
            ]);

        $this->assertSame($id_logger, $this->repository->createLog($parameters));
    }
}
