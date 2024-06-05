<?php

namespace PayPlug\tests\models\repositories\ShopRepository;

/**
 * @group unit
 * @group repository
 * @group shop_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getActiveShopUrlTest extends BaseShopRepository
{
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
