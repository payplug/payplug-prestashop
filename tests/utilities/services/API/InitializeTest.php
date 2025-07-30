<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class InitializeTest extends BaseApi
{
    private $configuration_class;
    private $merchant;
    private $module;
    private $module_adapter;
    private $jwt;
    private $logger;
    private $token;
    private $client_data;

    public function setUp()
    {
        parent::setUp();
        $this->jwt = [
            'test' => [
                'access_token' => 'JWT_Token',
                'expires_date' => 1729753256,
            ],
            'live' => [
                'access_token' => 'JWT_Token',
                'expires_date' => 1729753256,
            ],
        ];
        $this->client_data = [
            'test' => [
                'client_id' => 'client_id_test',
                'client_secret' => 'client_secret_test',
            ],
            'live' => [
                'client_id' => 'client_id_live',
                'client_secret' => 'client_secret_live',
            ],
        ];
        $this->token = 'live_api_key';
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->merchant = \Mockery::mock('Merchant');
        $this->module = \Mockery::mock('Module');
        $this->module->shouldReceive([
            'getService' => $this->merchant,
        ]);
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration_class,
            'getModule' => $this->module_adapter,
            'getLogger' => $this->logger,
            'getApiVersion' => 'api_version',
        ]);
        $this->service->shouldReceive([
            'setUserAgent' => true,
        ]);
    }

    public function testWhenAnExceptionIsThrown()
    {
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn($this->token);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(null);

        $this->api
            ->shouldReceive('init')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            null,
            $this->service->initialize(true)
        );
    }

    public function testWhenApiIsInitializedWithApiKeys()
    {
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn($this->token);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(null);

        $resource = \Mockery::mock('PayPlugAPI');
        $this->api->shouldReceive([
            'init' => $resource,
        ]);

        $this->assertSame(
            $resource,
            $this->service->initialize(true)
        );
    }

    public function testWhenJWTIsExpiredAndCantBeGetted()
    {
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn($this->token);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(json_encode($this->jwt));
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('client_data')
            ->andReturn(json_encode($this->client_data));
        $this->merchant->shouldReceive([
            'generateJWT' => false,
        ]);

        $this->assertSame(
            null,
            $this->service->initialize(true)
        );
    }

    public function testWhenJWTIsExpiredAndCantBeRegister()
    {
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn($this->token);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(json_encode($this->jwt));
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('client_data')
            ->andReturn(json_encode($this->client_data));
        $this->merchant->shouldReceive([
            'generateJWT' => [
                'data' => $this->jwt,
            ],
            'registerJWT' => false,
        ]);

        $this->assertSame(
            null,
            $this->service->initialize(true)
        );
    }

    public function testWhenJWTIsExpiredAndRenew()
    {
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn($this->token);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('jwt')
            ->andReturn(json_encode($this->jwt));
        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('client_data')
            ->andReturn(json_encode($this->client_data));
        $this->merchant->shouldReceive([
            'generateJWT' => [
                'data' => $this->jwt,
            ],
            'registerJWT' => true,
        ]);

        $resource = \Mockery::mock('PayPlugAPI');
        $this->api->shouldReceive([
            'init' => $resource,
        ]);

        $this->assertSame(
            $resource,
            $this->service->initialize(true)
        );
    }
}
