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
class getSettingsSectionTest extends BaseApiRest
{
    public function setUp()
    {
        parent::setUp();
        $context = \Mockery::mock('Context');
        $context->shouldReceive([
            'get' => ContextMock::get(),
        ]);

        $this->configuration_class
            ->shouldReceive('getDefault')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'email':
                        return '';
                    case 'sandbox_mode':
                        return 1;
                    default:
                        return $key;
                }
            })
        ;

        $this->plugin->shouldReceive([
            'getContext' => $context,
        ]);

        $this->classe->shouldReceive([
            'getDeferredState' => [],
        ]);
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
            $this->classe->getSettingsSection($current_configuration)
        );
    }

    public function testWhenGivenEmailIsFalse()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
                                        'checkPsAccount' => true,
                                    ]);
        $this->dependencies->configClass = $configClass;
        $current_configuration = [
            'email' => '',
            'logged' => true,
            'mode' => 1,
            'psaccount' => true,
        ];
        $this->assertSame(
            $current_configuration,
            $this->classe->getSettingsSection($current_configuration)
        );
    }

    public function testWhenNoEmailGiven()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
                                        'checkPsAccount' => true,
                                    ]);
        $this->dependencies->configClass = $configClass;
        $current_configuration = [
            'logged' => true,
            'mode' => 1,
        ];
        $this->assertSame(
            [
                'email' => '',
                'logged' => true,
                'mode' => 1,
                'psaccount' => true,
            ],
            $this->classe->getSettingsSection($current_configuration)
        );
    }

    public function testWhenGivenLoggedIsFalse()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
                                        'checkPsAccount' => true,
                                    ]);
        $this->dependencies->configClass = $configClass;
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'logged' => false,
            'mode' => 1,
            'psaccount' => true,
        ];
        $this->assertSame(
            $current_configuration,
            $this->classe->getSettingsSection($current_configuration)
        );
    }

    public function testWhenNoLoggedGiven()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
                                        'checkPsAccount' => true,
                                    ]);
        $this->dependencies->configClass = $configClass;
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'mode' => 1,
        ];

        $this->assertSame(
            [
                'email' => 'unit.test@payplug.com',
                'logged' => false,
                'mode' => 1,
                'psaccount' => true,
            ],
            $this->classe->getSettingsSection($current_configuration)
        );
    }

    /**
     * @description  check if psaccount is not connected for pspl
     */
    public function testWhenPsaccountIsNotConnected()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
                                        'checkPsAccount' => false,
                                    ]);
        $this->dependencies->configClass = $configClass;
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'logged' => false,
            'mode' => 1,
            'psaccount' => false,
        ];
        $this->assertSame(
            $current_configuration,
            $this->classe->getSettingsSection($current_configuration)
        );
    }

    /**
     * @description  check when psaccount is connected for pspl
     */
    public function testWhenPsaccountIsConnected()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
                                        'checkPsAccount' => true,
                                    ]);
        $this->dependencies->configClass = $configClass;
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'logged' => false,
            'mode' => 1,
            'psaccount' => true,
        ];
        $this->assertSame(
            $current_configuration,
            $this->classe->getSettingsSection($current_configuration)
        );
    }

    /**
     * @description check that logged must be false when psaccount is disconnected
     */
    public function testNotLoggedWhenPsaccountIsNotConnected()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive(
            [
                'checkPsAccount' => false,
            ]
        );
        $this->dependencies->configClass = $configClass;

        $configurationClass = \Mockery::mock('ConfigurationClass');
        $current_configuration = [
        ];
        $configurationClass
            ->shouldReceive('getDefault')
            ->with('email')
            ->andReturn('unit.test@payplug.com');
        $configurationClass
            ->shouldReceive('getDefault')
            ->with('sandbox_mode')
            ->andReturn('1');
        $this->assertFalse(
            $this->classe->getSettingsSection($current_configuration)['logged']
        );
    }
}
