<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_classe
 */
class registerJWTTest extends BaseMerchant
{
    private $api_service;
    private $client_id;
    private $configuration_class;
    private $jwt;

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
        $this->plugin->shouldReceive([
            'getApiService' => $this->api_service,
        ]);
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
