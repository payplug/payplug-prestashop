<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 *
 * @runTestsInSeparateProcesses
 */
class getLoggedSectionTest extends BaseApiRest
{
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
     * @description  generate Not onborded merchant datas
     *
     * @return \Generator
     */
    public function unOnboardedMerchantDataProvider()
    {
        yield ['payplug', null, true];
        yield ['payplug', null, false];
        yield ['pspaylater', 'sk_live_4fuIk4dSh7Kkyu3sP78', false];
        yield ['pspaylater', null, false];
        yield ['pspaylater', null, true];
    }

    /**
     * @description  generate onborded merchant datas
     *
     * @return \Generator
     */
    public function OnboardedMerchantDataProvider()
    {
        yield ['payplug', 'sk_live_4fuIk4dSh7Kkyu3sP78', false];
        yield ['payplug', 'sk_live_4fuIk4dSh7Kkyu3sP78', true];
        yield ['pspaylater', 'sk_live_4fuIk4dSh7Kkyu3sP78', true];
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
            $this->classe->getLoggedSection($current_configuration)
        );
    }

    public function testWhenNoSandboxModeIsGiven()
    {
        $current_configuration = [];
        $this->dependencies->apiClass->shouldReceive(
            [
                'getAccountPermissions' => ['onboarding_oney_completed' => true],
            ]
        );
        $response = $this->classe->getLoggedSection($current_configuration);
        $this->assertSame(
            [
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'logged.mode.options.sandbox',
                    'value' => 1,
                    'checked' => true,
                ],
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'logged.mode.options.live',
                    'value' => 0,
                    'checked' => false,
                ],
            ],
            $response['options']
        );
    }

    public function testWhenModuleIsConfiguredOnSandboxMode()
    {
        $current_configuration = [
            'sandbox_mode' => true,
        ];
        $this->dependencies->apiClass->shouldReceive(
            [
                'getAccountPermissions' => ['onboarding_oney_completed' => true],
            ]
        );
        $response = $this->classe->getLoggedSection($current_configuration);
        $this->assertSame(
            [
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'logged.mode.options.sandbox',
                    'value' => 1,
                    'checked' => true,
                ],
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'logged.mode.options.live',
                    'value' => 0,
                    'checked' => false,
                ],
            ],
            $response['options']
        );
    }

    public function testWhenModuleIsConfiguredOnLiveMode()
    {
        $current_configuration = [
            'sandbox_mode' => false,
        ];
        $this->dependencies->apiClass->shouldReceive(
            [
                'getAccountPermissions' => ['onboarding_oney_completed' => true],
            ]
        );
        $response = $this->classe->getLoggedSection($current_configuration);
        $this->assertSame(
            [
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'logged.mode.options.sandbox',
                    'value' => 1,
                    'checked' => false,
                ],
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'logged.mode.options.live',
                    'value' => 0,
                    'checked' => true,
                ],
            ],
            $response['options']
        );
    }

    /**
     * @dataProvider unOnboardedMerchantDataProvider
     * @description test the cas of the merchant is not onboarded for both modules
     *
     * @param $module_name
     * @param $live_api_key
     * @param $onboarding_oney_completed
     */
    public function testWhenMerchantIsntOnboarded($module_name, $live_api_key, $onboarding_oney_completed)
    {
        $current_configuration = [
            'sandbox_mode' => false,
        ];
        $this->dependencies->name = $module_name;
        $this->configuration_class
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) use ($live_api_key) {
                switch ($key) {
                    case 'live_api_key':
                        return $live_api_key;
                }
            })
        ;

        $this->dependencies->apiClass->shouldReceive(
            [
                'getAccountPermissions' => ['onboarding_oney_completed' => $onboarding_oney_completed],
            ]
        );
        $response = $this->classe->getLoggedSection($current_configuration);
        $this->assertSame(
            [
                'inactive' => true,
                'title' => 'logged.inactive.modal.title',
                'description' => 'logged.inactive.modal.description',
                'password_label' => 'logged.inactive.modal.password_label',
                'cancel' => 'logged.inactive.modal.cancel',
                'ok' => 'logged.inactive.modal.ok',

            ],
            $response['inactive_modal']
        );
    }

    /**
     * @dataProvider OnboardedMerchantDataProvider
     * @description test the cas of the merchant is onboarded for both modules
     *
     * @param $module_name
     * @param $live_api_key
     * @param $onboarding_oney_completed
     */
    public function testWhenMerchantIsOnboarded($module_name, $live_api_key, $onboarding_oney_completed)
    {
        $current_configuration = [
            'sandbox_mode' => false,
        ];
        $this->dependencies->name = $module_name;
        $this->configuration_class
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) use ($live_api_key) {
                switch ($key) {
                    case 'live_api_key':
                        return $live_api_key;
                }
            })
        ;

        $this->dependencies->apiClass->shouldReceive(
            [
                'getAccountPermissions' => ['onboarding_oney_completed' => $onboarding_oney_completed],
            ]
        );
        $response = $this->classe->getLoggedSection($current_configuration);
        $this->assertSame(
            [
                'inactive' => false,
                'title' => 'logged.inactive.modal.title',
                'description' => 'logged.inactive.modal.description',
                'password_label' => 'logged.inactive.modal.password_label',
                'cancel' => 'logged.inactive.modal.cancel',
                'ok' => 'logged.inactive.modal.ok',

            ],
            $response['inactive_modal']
        );
    }
}
