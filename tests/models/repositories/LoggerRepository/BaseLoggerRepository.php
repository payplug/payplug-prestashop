<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

use PayPlug\src\models\repositories\LoggerRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseLoggerRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(LoggerRepository::class, [$this->dependencies])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($arg) {
                return (string) $arg;
            });
        $this->repository
            ->shouldReceive('getTableName')
            ->andReturnUsing(function ($value) {
                return $value;
            });

        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'payplug_logger',
                'primary' => 'id_payplug_logger',
                'fields' => [
                    'process' => ['type' => 'string', 'required' => true],
                    'content' => ['type' => 'string', 'required' => true],
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
