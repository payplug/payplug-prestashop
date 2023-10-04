<?php

namespace PayPlug\tests\models\repositories\CacheRepository;

use PayPlug\src\models\repositories\CacheRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseCacheRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(CacheRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }
}
