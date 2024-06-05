<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getLastLimitLogTest extends BaseLoggerRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $limit
     */
    public function testWhenGivenLimitIsInvalidIntegerFormat($limit)
    {
        $this->assertSame([], $this->repository->getLastLimitLog($limit));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $limit = 42;
        $this
            ->repository
            ->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'orderBy' => $this->repository,
                'limit' => $this->repository,
                'build' => [],
            ]);

        $this->assertSame([], $this->repository->getLastLimitLog($limit));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $limit = 42;
        $date = date('Y-m-d H:i:s');
        $logger = [
            'id_payplug_logger' => 42,
            'process' => 'process',
            'content' => '{}',
            'date_add' => $date,
            'date_upd' => $date,
        ];

        $this
            ->repository
            ->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'orderBy' => $this->repository,
                'limit' => $this->repository,
                'build' => $logger,
            ]);

        $this->assertSame($logger, $this->repository->getLastLimitLog($limit));
    }
}
