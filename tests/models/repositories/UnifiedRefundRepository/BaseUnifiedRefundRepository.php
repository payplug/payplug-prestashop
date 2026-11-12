<?php

namespace PayPlug\tests\models\repositories\UnifiedRefundRepository;

use PayPlug\src\models\repositories\UnifiedRefundRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseUnifiedRefundRepository extends BaseRepository
{
    public function setUp(): void
    {
        parent::setUp();
        $this->repository = \Mockery::mock(UnifiedRefundRepository::class, [$this->dependencies])
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
                'table' => 'payplug_unified_refund',
                'primary' => 'id_payplug_unified_refund',
                'fields' => [
                    'refund_operation_id' => ['type' => 'string', 'required' => true],
                    'payment_operation_id' => ['type' => 'string', 'required' => true],
                    'order_id' => ['type' => 'string', 'required' => true],
                    'amount' => ['type' => 'integer', 'required' => true],
                    'currency' => ['type' => 'string', 'required' => true],
                    'status' => ['type' => 'string', 'required' => true],
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
