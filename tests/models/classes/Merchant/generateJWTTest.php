<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_classe
 *
 * @runTestsInSeparateProcesses
 */
class generateJWTTest extends BaseMerchant
{
    public $client_datas;
    public $client_id;
    public $generated_jwt;
    public $jwt;

    public function setUp()
    {
        parent::setUp();
        $this->client_datas = [
            'test' => [
                'client_id' => 'some_client_id_test',
                'client_secret' => 'some_client_secret_test',
            ],
            'live' => [
                'client_id' => 'some_client_id_live',
                'client_secret' => 'some_client_secret_live',
            ],
        ];
        $this->client_id = 'client_id';
        $this->generated_jwt = [
            'result' => true,
            'code' => 200,
            'data' => [
                'access_token' => 'JWT_Token',
                'expires_in' => 3599,
                'scope' => '',
                'token_type' => 'bearer',
            ],
        ];
        $this->jwt = [
            'test' => [
                'access_token' => 'JWT_Token',
                'expires_in' => 3599,
                'scope' => '',
                'token_type' => 'bearer',
            ],
            'live' => [
                'access_token' => 'JWT_Token',
                'expires_in' => 3599,
                'scope' => '',
                'token_type' => 'bearer',
            ],
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * * @param mixed $client_datas
     */
    public function testWhenGivenClientDatasIsNotValidArray($client_datas)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong $client_datas given',
            ],
            $this->class->generateJWT($client_datas)
        );
    }

    public function testWhenJWTEmptyResult()
    {
        $this->api_service->shouldReceive([
            'generateJWT' => [
                'result' => false,
                'code' => 400,
                'message' => 'Some error message',
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Error during JWT generation',
            ],
            $this->class->generateJWT($this->client_datas)
        );
    }

    public function testWhenJWTGenerated()
    {
        $this->api_service->shouldReceive([
            'generateJWT' => $this->generated_jwt,
        ]);
        $this->assertSame(
            [
                'result' => true,
                'data' => $this->jwt,
            ],
            $this->class->generateJWT($this->client_datas)
        );
    }
}
