<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

use PayPlug\src\models\repositories\OrderStateRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseOrderStateRepository extends BaseRepository
{
    protected $repository;

    public function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(OrderStateRepository::class, [$this->dependencies])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->repository->shouldReceive('escape')
            ->andReturnUsing(function ($arg) {
                return (string) $arg;
            });
        $this->repository->shouldReceive('getTableName')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }
}
