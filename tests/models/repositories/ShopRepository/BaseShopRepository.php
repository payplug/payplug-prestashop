<?php

namespace PayPlug\tests\models\repositories\ShopRepository;

use PayPlug\src\models\repositories\ShopRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseShopRepository extends BaseRepository
{
    public function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(ShopRepository::class, [$this->dependencies])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->repository->shouldReceive('getTableName')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }
}
