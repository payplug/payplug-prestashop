<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 * @group dev
 */
class getApiBearerTest extends BaseApi
{
    public $is_live;
    public $configuration;
    public $token;
    public $jwt;
    public $oauth_client_data;

    public function setUp()
    {
        parent::setUp();
        $this->is_live = true;
        $this->configuration = \Mockery::mock('ConfigurationClass');
        $this->token = 'bearer_token';

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getLogger' => $this->logger,
        ]);

        $this->configuration->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn($this->token);

        $this->jwt = [
            'test' => [
                'access_token' => 'JWT_Token',
                'expires_date' => time() + 300,
            ],
            'live' => [
                'access_token' => 'JWT_Token',
                'expires_date' => time() + 300,
            ],
        ];
        $this->oauth_client_data = [
            'test' => [
                'client_id' => 'client_id_test',
                'client_secret' => 'client_secret_test',
            ],
            'live' => [
                'client_id' => 'client_id_live',
                'client_secret' => 'client_secret_live',
            ],
        ];
    }

    public function testWhenApiBearerIsGetFromLoginRoute()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn('');
        $this->configuration->shouldReceive('getValue')
            ->with('oauth_client_data')
            ->andReturn('');
        $this->assertSame(
            $this->token,
            $this->service->getApiBearer($this->is_live)
        );
    }

    public function testWhenJWTValidationThrowsException()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(json_encode($this->jwt));
        $this->configuration->shouldReceive('getValue')
            ->with('oauth_client_data')
            ->andReturn(json_encode($this->oauth_client_data));

        $this->authentication
            ->shouldReceive('validateJWT')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            '',
            $this->service->getApiBearer($this->is_live)
        );
    }

    public function testWhenJWTValidationReturnEmptyToken()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(json_encode($this->jwt));
        $this->configuration->shouldReceive('getValue')
            ->with('oauth_client_data')
            ->andReturn(json_encode($this->oauth_client_data));

        $this->authentication
            ->shouldReceive([
                'validateJWT' => [
                    'no_token_given',
                ],
            ]);

        $this->assertSame(
            '',
            $this->service->getApiBearer($this->is_live)
        );
    }

    public function testWhenJWTValidationReturnToken()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(json_encode($this->jwt));
        $this->configuration->shouldReceive('getValue')
            ->with('oauth_client_data')
            ->andReturn(json_encode($this->oauth_client_data));

        $this->authentication
            ->shouldReceive([
                'validateJWT' => [
                    'token' => $this->jwt['live'],
                    'need_update' => false,
                ],
            ]);

        $this->assertSame(
            'JWT_Token',
            $this->service->getApiBearer($this->is_live)
        );
    }
}
