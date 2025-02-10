<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class getClientDataTest extends BaseApi
{
    public $company_id;
    public $client_name;
    public $mode;
    public $session;
    public $client_data;

    public function setUp()
    {
        parent::setUp();
        $this->session = 'session_token';
        $this->company_id = 'company_id';
        $this->mode = 'mode';
        $this->client_name = 'Client Name';
        $this->client_data = [
            'test' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
            ],
            'live' => [
                'client_id' => 'live_client_id',
                'client_secret' => 'live_client_secret',
            ],
        ];
        $this->plugin->shouldReceive([
            'getApiVersion' => 'api_version',
        ]);
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $company_id
     */
    public function testWhenGivenCompanyIdIsntValidString($company_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $company_id given',
            ],
            $this->service->getClientData($company_id, $this->client_name, $this->mode, $this->session)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $mode
     * @param mixed $client_name
     */
    public function testWhenGivenClientNameIsntValidString($client_name)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $client_name given',
            ],
            $this->service->getClientData($this->company_id, $client_name, $this->mode, $this->session)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $mode
     */
    public function testWhenGivenModeIsntValidString($mode)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $mode given',
            ],
            $this->service->getClientData($this->company_id, $this->client_name, $mode, $this->session)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $session
     */
    public function testWhenGivenSessionIsntValidString($session)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $session given',
            ],
            $this->service->getClientData($this->company_id, $this->client_name, $this->mode, $session)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $resource = \Mockery::mock('PayPlugAPI');
        $this->api->shouldReceive([
            'init' => $resource,
        ]);

        $this->authentication
            ->shouldReceive('createClientIdAndSecret')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->getClientData($this->company_id, $this->client_name, $this->mode, $this->session)
        );
    }

    public function testWhenClientDataIsGetted()
    {
        $resource = \Mockery::mock('PayPlugAPI');
        $this->api->shouldReceive([
            'init' => $resource,
        ]);

        $this->authentication->shouldReceive([
            'createClientIdAndSecret' => $this->client_data,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'data' => $this->client_data,
            ],
            $this->service->getClientData($this->company_id, $this->client_name, $this->mode, $this->session)
        );
    }
}
