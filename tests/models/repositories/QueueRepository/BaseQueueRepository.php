<?php

namespace PayPlug\tests\models\repositories\QueueRepository;

use PayPlug\src\models\repositories\QueueRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseQueueRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(QueueRepository::class, [$this->dependencies])
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
                'table' => 'payplug_queue',
                'primary' => 'id_payplug_queue',
                'fields' => [
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                    'id_cart' => ['type' => 'integer', 'required' => true],
                    'resource_id' => ['type' => 'string', 'required' => true],
                    'treated' => ['type' => 'boolean'],
                    'type' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
