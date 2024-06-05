<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getAllLogTest extends BaseLoggerRepository
{
    public function testWhenNoLogsIsReturnForGivenCustomer()
    {
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getAllLog()
        );
    }

    public function testWhenLogsIsReturnForGivenCustomer()
    {
        $date = date('Y-m-d H:i:s');
        $loggers = [
            [
                'id_payplug_logger' => 42,
                'process' => 'process',
                'content' => '{}',
                'date_add' => $date,
                'date_upd' => $date,
            ],
        ];
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $loggers,
        ]);

        $this->assertSame(
            $loggers,
            $this->repository->getAllLog()
        );
    }
}
