<?php

namespace PayPlug\tests\models\repositories\OrderPaymentRepository;

use PayPlug\src\models\repositories\OrderPaymentRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseOrderPaymentRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(OrderPaymentRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($arg) {
                return (string) $arg;
            });
    }
}
