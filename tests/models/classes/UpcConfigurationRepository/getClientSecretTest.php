<?php

namespace PayPlug\tests\models\classes\UpcConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getClientSecretTest extends BaseUpcConfigurationRepository
{
    public function testDelegatesToMerchant()
    {
        $this->merchant->shouldReceive('getOauthClientField')->once()->with('client_secret')->andReturn('test_secret');

        $this->assertSame('test_secret', $this->repository->getClientSecret());
    }
}
