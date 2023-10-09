<?php

namespace PayPlug\tests\models\repositories\LockRepository;

use PayPlug\src\models\repositories\LockRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseLockRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(LockRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }
}
