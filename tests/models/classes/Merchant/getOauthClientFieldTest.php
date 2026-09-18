<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group merchant
 */
class getOauthClientFieldTest extends BaseMerchant
{
    public function testResolvesTheLiveClientIdWhenNotInSandboxMode()
    {
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('0');
        $this->configuration_class->shouldReceive('getValue')->with('oauth_client_data')->andReturn(
            json_encode(['test' => ['client_id' => 'test_id', 'client_secret' => 'test_secret'], 'live' => ['client_id' => 'live_id', 'client_secret' => 'live_secret']])
        );

        $this->assertSame('live_id', $this->class->getOauthClientField('client_id'));
    }

    public function testResolvesTheTestClientSecretWhenInSandboxMode()
    {
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('1');
        $this->configuration_class->shouldReceive('getValue')->with('oauth_client_data')->andReturn(
            json_encode(['test' => ['client_id' => 'test_id', 'client_secret' => 'test_secret'], 'live' => ['client_id' => 'live_id', 'client_secret' => 'live_secret']])
        );

        $this->assertSame('test_secret', $this->class->getOauthClientField('client_secret'));
    }

    public function testReturnsEmptyStringWhenOauthClientDataIsMissingForTheMode()
    {
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('0');
        $this->configuration_class->shouldReceive('getValue')->with('oauth_client_data')->andReturn(
            json_encode(['test' => ['client_id' => 'test_id', 'client_secret' => 'test_secret']])
        );

        $this->assertSame('', $this->class->getOauthClientField('client_id'));
    }

    public function testReturnsEmptyStringWhenFieldArgumentIsEmpty()
    {
        $this->assertSame('', $this->class->getOauthClientField(''));
    }
}
