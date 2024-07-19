<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @runTestsInSeparateProcesses
 */
class getAllLogTest extends BaseLoggerRepository
{
    public function testWhenNoEntityNameDefined()
    {
        $this->repository->entity_name = '';
        $this->assertSame([], $this->repository->getAllLog());
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertSame([], $this->repository->getAllLog());
    }

    public function testWhenNoLogsIsReturnForGivenCustomer()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
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
            'getEntityObject' => $this->entity,
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
