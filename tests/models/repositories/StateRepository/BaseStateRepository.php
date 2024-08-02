<?php

namespace PayPlug\tests\models\repositories\StateRepository;

use PayPlug\src\models\repositories\StateRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseStateRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(StateRepository::class, [$this->dependencies])
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
                'table' => 'payplug_order_state',
                'primary' => 'id_payplug_order_state',
                'fields' => [
                    'id_order_state' => ['type' => 'integer', 'required' => true],
                    'type' => ['type' => 'string', 'required' => true],
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
