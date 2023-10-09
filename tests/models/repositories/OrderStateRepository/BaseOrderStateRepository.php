<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

use PayPlug\src\models\repositories\OrderStateRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseOrderStateRepository extends BaseRepository
{
    protected $repository;

    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(OrderStateRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($arg) {
                return (string) $arg;
            });
    }
}
