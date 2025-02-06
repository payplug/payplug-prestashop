<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_classe
 *
 * @runTestsInSeparateProcesses
 */
class registerJWTTest extends BaseMerchant
{
    public $client_id;
    public $configuration_class;
    public $jwt;

    public function setUp()
    {
        parent::setUp();
        $this->api_service = \Mockery::mock('ApiService');
        $this->client_id = 'client_id';
        $this->jwt = [
            'result' => true,
            'code' => 200,
            'data' => [
                'access_token' => 'JWT_Token',
                'expires_in' => 3599,
                'scope' => '',
                'token_type' => 'bearer',
            ],
        ];
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration_class,
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * * @param mixed $jwt
     */
    public function testWhenGivenJWTIsNotValidArray($jwt)
    {
        $this->assertSame(
            false,
            $this->class->registerJWT($jwt)
        );
    }

    public function testWhenJWTRegistered()
    {
        $this->configuration_class->shouldReceive([
            'set' => true,
        ]);

        $this->assertSame(
            true,
            $this->class->registerJWT($this->jwt)
        );
    }
}
