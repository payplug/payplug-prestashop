<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class GenerateJWTOneShotTest extends BaseApi
{
    public $client_id;
    public $redirect_uri;
    public $code_verifier;
    public $authorization_code;
    public $jwt;
    public $id_token;
    public $email;

    public function setUp()
    {
        parent::setUp();
        $this->client_id = 'some_client_id';
        $this->redirect_uri = 'some_redirect_uri';
        $this->code_verifier = 'some_code_verifier';
        $this->authorization_code = 'some_authorization_code';
        $this->jwt = 'some_jwt_one_shot';
        $this->id_token = 'some_id_token.ewogICJlbWFpbCI6ICJ0ZXN0QHRlc3QuY29tIgp9';
        $this->email = 'test@test.com';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $authorization_code
     */
    public function testWhenGivenAuthorizationCodeIsntValidString($authorization_code)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $authorization_code given',
            ],
            $this->service->generateJWTOneShot($authorization_code, $this->redirect_uri, $this->client_id, $this->code_verifier)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $redirect_uri
     */
    public function testWhenGivenRedirectUriIsntValidString($redirect_uri)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $redirect_uri given',
            ],
            $this->service->generateJWTOneShot($this->authorization_code, $redirect_uri, $this->client_id, $this->code_verifier)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $client_id
     */
    public function testWhenGivenClientIdIsntValidString($client_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $client_id given',
            ],
            $this->service->generateJWTOneShot($this->authorization_code, $this->redirect_uri, $client_id, $this->code_verifier)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $code_verifier
     */
    public function testWhenGivenCodeVerifierIsntValidString($code_verifier)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $code_verifier given',
            ],
            $this->service->generateJWTOneShot($this->authorization_code, $this->redirect_uri, $this->client_id, $code_verifier)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->authentication
            ->shouldReceive('generateJWTOneShot')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->generateJWTOneShot($this->authorization_code, $this->redirect_uri, $this->client_id, $this->code_verifier)
        );
    }

    public function testWhenJWTOneShotIsGenerated()
    {
        $this->authentication
            ->shouldReceive([
                'generateJWTOneShot' => [
                    'httpResponse' => [
                        'access_token' => $this->jwt,
                        'id_token' => $this->id_token,
                    ],
                ],
            ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'data' => $this->jwt,
                'email' => $this->email,
            ],
            $this->service->generateJWTOneShot($this->authorization_code, $this->redirect_uri, $this->client_id, $this->code_verifier)
        );
    }
}
