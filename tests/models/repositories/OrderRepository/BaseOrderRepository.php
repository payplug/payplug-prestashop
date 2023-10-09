<?php

namespace PayPlug\tests\models\repositories\OrderRepository;

use PayPlug\src\models\repositories\OrderRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseOrderRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(OrderRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($arg) {
                return (string) $arg;
            });
    }
}
