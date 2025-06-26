<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_class
 */
class isLoggedTest extends BaseMerchant
{
    public $account_validator;

    public function setUp()
    {
        parent::setUp();
        $this->configuration_class->shouldReceive('getValue')
            ->with('test_api_key')
            ->andReturn('test_api_key');
        $this->configuration_class->shouldReceive('getValue')
            ->with('email')
            ->andReturn('merchant@payplug.com');
        $this->account_validator = \Mockery::mock('AccountValidator');
        $this->dependencies->shouldReceive([
            'getValidators' => [
                'account' => $this->account_validator,
            ],
        ]);
    }

    public function testWhenEmailIsInvalid()
    {
        $this->account_validator->shouldReceive([
            'isEmail' => [
                'result' => false,
            ],
        ]);
        $this->configuration_class->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn('{}');
        $this->assertFalse($this->class->isLogged());
    }

    public function testWhenJWTIsInvalid()
    {
        $this->account_validator->shouldReceive([
            'isEmail' => [
                'result' => true,
            ],
        ]);
        $this->configuration_class->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn('{"test":{}}');
        $this->assertFalse($this->class->isLogged());
    }

    public function testWhenJWTIsValid()
    {
        $this->account_validator->shouldReceive([
            'isEmail' => [
                'result' => true,
            ],
        ]);
        $this->configuration_class->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn('{"test":{"access_token":"valid_jwt_token"}}');
        $this->assertTrue($this->class->isLogged());
    }
}
