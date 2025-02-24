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

    public function setUp()
    {
        parent::setUp();
        $this->client_id = 'some_client_id';
        $this->redirect_uri = 'some_redirect_uri';
        $this->code_verifier = 'some_code_verifier';
        $this->authorization_code = 'some_authorization_code';
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

    public function testWhenAPiCantBeInitialize()
    {
        $this->service->shouldReceive([
            'initialize' => false,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'Cannot connect to the API',
            ],
            $this->service->generateJWTOneShot($this->authorization_code, $this->redirect_uri, $this->client_id, $this->code_verifier)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

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
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->authentication
            ->shouldReceive([
                'generateJWTOneShot' => '{"access_token": "some_token","expires_in": 299,"scope": "","token_type": "bearer"}',
            ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'data' => '{"access_token": "some_token","expires_in": 299,"scope": "","token_type": "bearer"}',
            ],
            $this->service->generateJWTOneShot($this->authorization_code, $this->redirect_uri, $this->client_id, $this->code_verifier)
        );
    }
}
