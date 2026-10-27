<?php

namespace PayPlug\tests\unified\contract\UnifiedConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getClientIdTest extends BaseUnifiedConfigurationRepository
{
    public function testDelegatesToMerchant()
    {
        $this->merchant->shouldReceive('getOauthClientField')->once()->with('client_id')->andReturn('live_id');

        $this->assertSame('live_id', $this->repository->getClientId());
    }
}
