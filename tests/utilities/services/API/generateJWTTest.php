<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class generateJWTTest extends BaseApi
{
    protected $plugin;
    private $client_id;
    private $client_secret;
    private $client_data;
    private $jwt;

    public function setUp()
    {
        parent::setUp();
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
        $this->client_id = 'some_client_id';
        $this->client_secret = 'some_client_secret';
        $this->jwt = [
            'access_token' => 'JWT_Token',
            'expires_in' => 3599,
            'scope' => '',
            'token_type' => 'bearer',
        ];
        $this->plugin->shouldReceive([
            'getApiVersion' => 'api_version',
        ]);
        $this->session = 'session_token';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $client_id
     */
    public function testWhenGivenClientIdIsNotValidString($client_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $client_id given',
            ],
            $this->service->generateJWT($client_id, $this->client_secret)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $client_secret
     */
    public function testWhenGivenClientSecretIsNotValidString($client_secret)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $client_secret given',
            ],
            $this->service->generateJWT($this->client_id, $client_secret)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $resource = \Mockery::mock('PayPlugAPI');
        $this->api->shouldReceive([
            'init' => $resource,
        ]);

        $this->authentication
            ->shouldReceive('generateJWT')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->generateJWT($this->client_id, $this->client_secret)
        );
    }

    public function testWhenJWTGenerated()
    {
        $resource = \Mockery::mock('PayPlugAPI');
        $this->api->shouldReceive([
            'init' => $resource,
        ]);

        $this->authentication->shouldReceive([
            'generateJWT' => $this->jwt,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'data' => $this->jwt,
            ],
            $this->service->generateJWT($this->client_id, $this->client_secret)
        );
    }
}
