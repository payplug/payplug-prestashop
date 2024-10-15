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
    private $session;
    private $client_data;

    public function setUp()
    {
        parent::setUp();
        $this->session = 'session_token';
        $this->client_data = [
            'client_id' => 'client-id-key',
            'client_secret' => 'clientSecretKey',
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
            $this->service->getClientData($session)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->authentication
            ->shouldReceive('getClientData')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->getClientData($this->session)
        );
    }

    public function testWhenClientDataIsGetted()
    {
        $this->authentication->shouldReceive([
            'getClientData' => $this->client_data,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'data' => $this->client_data,
            ],
            $this->service->getClientData($this->session)
        );
    }
}
