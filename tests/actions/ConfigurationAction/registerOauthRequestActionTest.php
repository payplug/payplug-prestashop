<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @runTestsInSeparateProcesses
 */
class registerOauthRequestActionTest extends BaseConfigurationAction
{
    public $client_id;
    public $company_id;
    public $context;
    public $context_adapter;

    public function setUp()
    {
        parent::setUp();

        $this->client_id = 'client_id';
        $this->company_id = 'company_id';
        $this->configuration_class->shouldReceive([
            'set' => true,
        ]);

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
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $client_id
     */
    public function testWhenGivenClientIdIsInvalidStringFormat($client_id)
    {
        $this->assertFalse($this->action->registerOauthRequestAction($client_id, $this->company_id));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $company_id
     */
    public function testWhenGivenCompanyIdIsInvalidStringFormat($company_id)
    {
        $this->assertFalse($this->action->registerOauthRequestAction($this->client_id, $company_id));
    }

    public function testWhenOAuthCantBeInitialized()
    {
        $this->api_service->shouldReceive([
            'initiateOAuth' => false,
        ]);
        $this->assertFalse($this->action->registerOauthRequestAction($this->client_id, $this->company_id));
    }

    public function testWhenOAuthIsInitialized()
    {
        $this->api_service->shouldReceive([
            'initiateOAuth' => true,
        ]);
        $this->assertTrue($this->action->registerOauthRequestAction($this->client_id, $this->company_id));
    }
}
