<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

use PayPlug\src\models\repositories\PaymentRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BasePaymentRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(PaymentRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }
}
