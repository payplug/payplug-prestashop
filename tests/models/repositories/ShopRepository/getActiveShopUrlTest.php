<?php

namespace PayPlug\tests\models\repositories\ShopRepository;

use PayPlug\src\models\repositories\ShopRepository;
use PayPlug\tests\models\repositories\BaseRepository;

/**
 * @group unit
 * @group repository
 * @group shop_repository
 *
 * @runTestsInSeparateProcesses
 */
class getActiveShopUrlTest extends BaseRepository
{
    protected function setUp()
    {
        $this->repository = \Mockery::mock(ShopRepository::class)->makePartial();
    }

    public function testWhenNoActiveShopUrlFound()
    {
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getActiveShopUrl()
        );
    }

    public function testWhenActiveShopUrlAreFound()
    {
        $shop = [
            'domain' => 'website.domain.com',
            'default' => '1',
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [$shop],
        ]);

        $this->assertSame(
            [$shop],
            $this->repository->getActiveShopUrl()
        );
    }
}
