<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class InitializeTest extends BaseApi
{
    private $token;

    public function setUp()
    {
        parent::setUp();
        $this->token = 'api_token';
        $this->service->shouldReceive([
            'setUserAgent' => true,
        ]);
        $this->plugin->shouldReceive([
            'getApiVersion' => 'api_version',
        ]);
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $token
     */
    public function testWhenGivenTokenIsntValidString($token)
    {
        $this->service->shouldReceive([
            'getCurrentApiKey' => null,
        ]);
        $this->assertSame(
            null,
            $this->service->initialize($token)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->api
            ->shouldReceive('init')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            null,
            $this->service->initialize($this->token)
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
            $this->service->initialize($this->token)
        );
    }
}
