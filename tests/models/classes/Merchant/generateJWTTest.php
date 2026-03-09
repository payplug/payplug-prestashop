<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group class
 * @group merchant_class
 */
class generateJWTTest extends BaseMerchant
{
    public $oauth_client_data;
    public $client_id;
    public $generated_jwt;
    public $jwt;

    public function setUp()
    {
        parent::setUp();
        $this->oauth_client_data = [
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
     * * @param mixed $oauth_client_data
     */
    public function testWhenGivenClientDatasIsNotValidArray($oauth_client_data)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong $oauth_client_data given',
            ],
            $this->class->generateJWT($oauth_client_data)
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
                'code' => 400,
                'message' => 'Some error message',
            ],
            $this->class->generateJWT($this->oauth_client_data)
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
            $this->class->generateJWT($this->oauth_client_data)
        );
    }
}
