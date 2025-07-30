<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class getClientDataTest extends BaseApi
{
    private $session;
    private $company_id;
    private $mode;
    private $client_data;

    public function setUp()
    {
        parent::setUp();
        $this->session = 'session_token';
        $this->company_id = 'company_id';
        $this->mode = 'mode';
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
            $this->service->getClientData($session, $this->company_id, $this->mode)
        );
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
            $this->service->getClientData($this->session, $company_id, $this->mode)
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
            $this->service->getClientData($this->session, $this->company_id, $mode)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->authentication
            ->shouldReceive('createClientIdAndSecret')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->getClientData($this->session, $this->company_id, $this->mode)
        );
    }

    public function testWhenClientDataIsGetted()
    {
        $this->authentication->shouldReceive([
            'createClientIdAndSecret' => $this->client_data,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'data' => $this->client_data,
            ],
            $this->service->getClientData($this->session, $this->company_id, $this->mode)
        );
    }
}
