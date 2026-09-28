<?php

namespace PayPlug\tests\models\classes\UpcConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getClientIdTest extends BaseUpcConfigurationRepository
{
    public function testDelegatesToMerchant()
    {
        $this->merchant->shouldReceive('getOauthClientField')->once()->with('client_id')->andReturn('live_id');

        $this->assertSame('live_id', $this->repository->getClientId());
    }
}
