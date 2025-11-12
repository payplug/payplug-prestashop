<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 * @group debug
 */
class InitializeTest extends BaseApi
{
    public $configuration_class;
    public $logger;
    public $token;

    public function setUp()
    {
        parent::setUp();
        $this->token = 'bearer_token';
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration_class,
            'getLogger' => $this->logger,
            'getApiVersion' => 'api_version',
        ]);
        $this->service->shouldReceive([
            'setParameters' => true,
            'setUserAgent' => true,
            'getApiBearer' => $this->token,
        ]);
    }

    public function testWhenAnExceptionIsThrown()
    {
        $this->api
            ->shouldReceive('init')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            null,
            $this->service->initialize(true)
        );
    }

    public function testWhenApiIsInitialized()
    {
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
