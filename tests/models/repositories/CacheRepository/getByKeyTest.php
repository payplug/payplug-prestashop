<?php

namespace PayPlug\tests\models\repositories\CacheRepository;

/**
 * @group unit
 * @group repository
 * @group cache_repository
 *
 * @runTestsInSeparateProcesses
 */
class getByKeyTest extends BaseCacheRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsInvalidIntegerFormat($resource_id)
    {
        $this->assertSame([], $this->repository->getByKey($resource_id));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $pay_id = 'pay_azerty12345';
        $this
            ->repository
            ->shouldReceive([
                'getEntityObject' => $this->entity,
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => [],
            ]);

        $this->assertSame([], $this->repository->getByKey($pay_id));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $pay_id = 'pay_azerty12345';
        $cache = [
            'id_payplug_cache' => 42,
            'resource_id' => 'pay_azerty12345',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => '4cbaebd7df677672ac3d571012ea0498129a5314271b0c38603c66425560bf43',
            'schedules' => '',
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this
            ->repository
            ->shouldReceive([
                'getEntityObject' => $this->entity,
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => $cache,
            ]);

        $this->assertSame($cache, $this->repository->getByKey($pay_id));
    }
}
