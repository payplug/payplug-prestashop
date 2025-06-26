<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class oauthLoginActionTest extends BaseConfigurationAction
{
    public $context;
    public $context_adapter;
    public $authorization_code;
    public $merchant;
    public $jwt;

    public function setUp()
    {
        parent::setUp();

        $this->context = \Mockery::mock('Context');
        $this->context->link = \Mockery::mock('Link');
        $this->context->link->shouldReceive([
            'getAdminLink' => 'admin_link_url',
        ]);
        $this->context_adapter = \Mockery::mock('ContextAdapter');
        $this->context_adapter->shouldReceive([
            'get' => $this->context,
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $this->context_adapter,
        ]);

        $this->authorization_code = 'authorization_code';
        $this->configuration_class
            ->shouldReceive([
                'set' => true,
            ])

            ->shouldReceive('getValue')
            ->with('oauth_client_id')
            ->andReturn('oauth_client_id')

            ->shouldReceive('getValue')
            ->with('oauth_code_verifier')
            ->andReturn('oauth_code_verifier')

            ->shouldReceive('getValue')
            ->with('oauth_company_id')
            ->andReturn('oauth_company_id');

        $this->merchant = \Mockery::mock('Merchant');
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.models.classes.merchant')
            ->andReturn($this->merchant);

        $this->jwt = [
            'access_token' => 'JWT_ONE_SHOT',
            'expires_in' => 299,
            'expires_date' => time() + 299,
            'scope' => '',
            'token_type' => 'bearer',
        ];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $authorization_code
     */
    public function testWhenGivenAuthorizationCodeIsInvalidStringFormat($authorization_code)
    {
        $this->assertSame(
            [
                'message' => 'ConfigurationAction::OauthLoginAction - Invalid parameter given, $authorization_code must be a non empty string.',
                'result' => false,
            ],
            $this->action->oauthLoginAction($authorization_code)
        );
    }

    public function testWhenJWTOneShotCantBeRetrieved()
    {
        $this->api_service->shouldReceive([
            'generateJWTOneShot' => [
                'result' => false,
                'message' => 'an error occured',
                'email' => 'test@test.com',
            ],
        ]);
        $this->assertSame(
            [
                'message' => 'ConfigurationAction::OauthLoginAction - JWT one shot can\'t be got.',
                'result' => false,
            ],
            $this->action->oauthLoginAction($this->authorization_code)
        );
    }

    public function testWhenClientDataCantBeRetrieved()
    {
        $this->api_service->shouldReceive([
            'generateJWTOneShot' => [
                'result' => true,
                'code' => 200,
                'data' => $this->jwt['access_token'],
                'email' => 'test@test.com',
            ],
        ]);
        $this->merchant->shouldReceive([
            'getClientData' => [
                'result' => false,
                'message' => 'An error occured',
            ],
        ]);
        $this->assertSame(
            [
                'message' => 'ConfigurationAction::OauthLoginAction - Client data shot can\'t be got.',
                'result' => false,
            ],
            $this->action->oauthLoginAction($this->authorization_code)
        );
    }

    public function testWhenJWTCantBeRetrieved()
    {
        $this->api_service->shouldReceive([
            'generateJWTOneShot' => [
                'result' => true,
                'code' => 200,
                'data' => $this->jwt['access_token'],
                'email' => 'test@test.com',
            ],
        ]);
        $this->merchant->shouldReceive([
            'getClientData' => [
                'result' => true,
                'data' => 'some_oauth_client_data_array',
            ],
            'generateJWT' => [
                'result' => false,
                'message' => 'An error occured',
            ],
        ]);
        $this->assertSame(
            [
                'message' => 'ConfigurationAction::OauthLoginAction - JWT can\'t be got.',
                'result' => false,
            ],
            $this->action->oauthLoginAction($this->authorization_code)
        );
    }

    public function testWhenJWTIsRetrieved()
    {
        $this->api_service->shouldReceive([
            'generateJWTOneShot' => [
                'result' => true,
                'code' => 200,
                'data' => $this->jwt['access_token'],
                'email' => 'test@test.com',
            ],
            'getAccount' => true,
        ]);
        $this->merchant->shouldReceive([
            'getClientData' => [
                'result' => true,
                'data' => 'some_oauth_client_data_array',
            ],
            'generateJWT' => [
                'result' => true,
                'code' => 200,
                'data' => $this->jwt,
            ],
        ]);
        $this->assertSame(
            [
                'message' => 'User connected',
                'result' => true,
            ],
            $this->action->oauthLoginAction($this->authorization_code)
        );
    }
}
