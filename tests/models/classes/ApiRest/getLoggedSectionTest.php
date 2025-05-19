<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group class
 * @group apirest_class
 *
 * @runTestsInSeparateProcesses
 */
class getLoggedSectionTest extends BaseApiRest
{
    public $given_configuration;

    public function setUp()
    {
        parent::setUp();
        $context = \Mockery::mock('Context');
        $context->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $context,
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
            'description' => 'logged.inactive.modal.description',
            'password_label' => 'logged.inactive.modal.password_label',
            'cancel' => 'logged.inactive.modal.cancel',
            'ok' => 'logged.inactive.modal.ok',
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
            'description' => 'logged.inactive.modal.description',
            'password_label' => 'logged.inactive.modal.password_label',
            'cancel' => 'logged.inactive.modal.cancel',
            'ok' => 'logged.inactive.modal.ok',
        ];
        $this->assertSame(
            $expected,
            $this->class->getLoggedSection($this->given_configuration)['inactive_modal']
        );
    }
}
