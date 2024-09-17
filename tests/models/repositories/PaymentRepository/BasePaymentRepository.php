<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

use PayPlug\src\models\repositories\PaymentRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BasePaymentRepository extends BaseRepository
{
    public function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(PaymentRepository::class, [$this->dependencies])
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
                'table' => 'payplug_payment',
                'primary' => 'id_payplug_payment',
                'fields' => [
                    'resource_id' => ['type' => 'string', 'required' => true],
                    'method' => ['type' => 'string', 'required' => true],
                    'id_cart' => ['type' => 'integer', 'required' => true],
                    'cart_hash' => ['type' => 'string', 'required' => true],
                    'schedules' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
