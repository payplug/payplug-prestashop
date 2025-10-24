<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class InitiateOAuthTest extends BaseApi
{
    public $client_id;
    public $redirect_uri;
    public $code_verifier;

    public function setUp()
    {
        parent::setUp();
        $this->client_id = 'some_client_id';
        $this->redirect_uri = 'some_redirect_uri';
        $this->code_verifier = 'some_code_verifier';
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
            $this->service->initiateOAuth($client_id, $this->redirect_uri, $this->code_verifier)
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
            $this->service->initiateOAuth($this->client_id, $redirect_uri, $this->code_verifier)
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
            $this->service->initiateOAuth($this->client_id, $this->redirect_uri, $code_verifier)
        );
    }

    public function testWhenBadRequestExceptionIsThrown()
    {
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->plugin->shouldReceive([
            'getLogger' => $this->logger,
        ]);

        $this->assertFalse($this->service->initiateOAuth($this->client_id, $this->redirect_uri, $this->code_verifier));
    }
}
