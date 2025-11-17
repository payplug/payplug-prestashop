<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_class
 */
class isOnboardedTest extends BaseMerchant
{
    public function setUp()
    {
        parent::setUp();
        $this->configuration_class->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn('live_api_key');
        $this->account_validator = \Mockery::mock('AccountValidator');
    }

    public function testWhenJWTIsInvalid()
    {
        $this->configuration_class->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn('{"live":{}}');
        $this->assertFalse($this->class->isOnboarded());
    }

    public function testWhenOldApiKeyIsValid()
    {
        $this->configuration_class->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn('{}');
        $this->assertTrue($this->class->isOnboarded());
    }

    public function testWhenJWTIsValid()
    {
        $this->configuration_class->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn('{"live":{"access_token":"valid_jwt_token"}}');
        $this->assertTrue($this->class->isOnboarded());
    }
}
