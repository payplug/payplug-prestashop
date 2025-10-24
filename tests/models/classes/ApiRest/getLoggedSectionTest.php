<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group class
 * @group apirest_class
 */
class getLoggedSectionTest extends BaseApiRest
{
    public $given_configuration;

    public function setUp()
    {
        parent::setUp();
        $context = \Mockery::mock('Context');
        $context_mock = ContextMock::get();
        $link = \Mockery::mock('link');
        $link->shouldReceive([
            'getAdminLink' => 'admin_link',
        ]);
        $context_mock->link = $link;
        $context->shouldReceive([
            'get' => $context_mock,
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $context,
        ]);

        $this->api_service->shouldReceive([
            'getRegisterUrl' => [
                'result' => true,
                'redirection' => 'oauth_register_url',
            ],
        ]);

        $this->given_configuration = [
            'sandbox_mode' => false,
        ];

        $this->configuration_class
            ->shouldReceive('getDefault')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'sandbox_mode':
                        return 1;
                }
            })
        ;
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenConfigurationIsInvalidArrayFormat($current_configuration)
    {
        $this->assertSame(
            [],
            $this->class->getLoggedSection($current_configuration)
        );
    }

    public function testWhenNoPermissionsGiven()
    {
        $this->api_service->shouldReceive([
            'getAccount' => [],
        ]);
        $this->assertSame(
            [],
            $this->class->getLoggedSection($this->given_configuration)
        );
    }

    public function testWhenMerchantIsNotOnboarded()
    {
        $this->api_service->shouldReceive([
            'getAccount' => [
                'merchant_permission' => true,
            ],
        ]);
        $this->merchant_class->shouldReceive([
            'isOnboarded' => false,
        ]);

        $expected = [
            'inactive' => true,
            'title' => 'logged.inactive.modal.title',
            'description_1' => 'logged.inactive.modal.description_1',
            'description_2' => 'logged.inactive.modal.description_2',
            'cancel' => 'logged.inactive.modal.cancel',
            'oauth' => 'logged.inactive.modal.oauth',
            'oauth_url' => 'oauth_register_url',
        ];
        $this->assertSame(
            $expected,
            $this->class->getLoggedSection($this->given_configuration)['inactive_modal']
        );
    }

    public function testWhenMerchantIsOnboarded()
    {
        $this->api_service->shouldReceive([
            'getAccount' => [
                'merchant_permission' => true,
            ],
        ]);
        $this->merchant_class->shouldReceive([
            'isOnboarded' => true,
        ]);

        $expected = [
            'inactive' => false,
            'title' => 'logged.inactive.modal.title',
            'description_1' => 'logged.inactive.modal.description_1',
            'description_2' => 'logged.inactive.modal.description_2',
            'cancel' => 'logged.inactive.modal.cancel',
            'oauth' => 'logged.inactive.modal.oauth',
            'oauth_url' => 'oauth_register_url',
        ];
        $this->assertSame(
            $expected,
            $this->class->getLoggedSection($this->given_configuration)['inactive_modal']
        );
    }
}
