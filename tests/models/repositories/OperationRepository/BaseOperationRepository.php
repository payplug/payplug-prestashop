<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

use PayPlug\src\models\repositories\OperationRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseOperationRepository extends BaseRepository
{
    public function setUp(): void
    {
        parent::setUp();
        $this->repository = \Mockery::mock(OperationRepository::class, [$this->dependencies])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->repository->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
        $this->repository->shouldReceive('getTableName')
            ->andReturnUsing(function ($value) {
                return $value;
            });

        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'payplug_upc_operation',
                'primary' => 'id_payplug_upc_operation',
                'fields' => [
                    'operation_id' => ['type' => 'string', 'required' => true],
                    'order_id' => ['type' => 'string', 'required' => true],
                    'exec_code' => ['type' => 'string', 'required' => true],
                    'outcome' => ['type' => 'string', 'required' => true],
                    'amount' => ['type' => 'integer', 'required' => true],
                    'treated' => ['type' => 'boolean'],
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
