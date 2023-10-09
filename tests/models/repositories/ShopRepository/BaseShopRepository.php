<?php

namespace PayPlug\tests\models\repositories\ShopRepository;

use PayPlug\src\models\repositories\ShopRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseShopRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(ShopRepository::class, ['prefix', $this->dependencies])->makePartial();
    }
}
