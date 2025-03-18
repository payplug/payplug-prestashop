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
class getSettingsSectionTest extends BaseApiRest
{
    public function setUp()
    {
        parent::setUp();
        $context = \Mockery::mock('Context');
        $context->shouldReceive([
            'get' => ContextMock::get(),
        ]);

        $this->configuration_class->shouldReceive('getDefault')
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

        $this->class->shouldReceive([
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
            $this->class->getSettingsSection($current_configuration)
        );
    }

    public function testWhenGivenEmailIsFalse()
    {
        $current_configuration = [
            'email' => '',
            'logged' => true,
            'mode' => 1,
        ];
        $this->assertSame(
            $current_configuration,
            $this->class->getSettingsSection($current_configuration)
        );
    }

    public function testWhenNoEmailGiven()
    {
        $current_configuration = [
            'logged' => true,
            'mode' => 1,
        ];
        $this->assertSame(
            [
                'email' => '',
                'logged' => true,
                'mode' => 1,
            ],
            $this->class->getSettingsSection($current_configuration)
        );
    }

    public function testWhenGivenLoggedIsFalse()
    {
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'logged' => false,
            'mode' => 1,
        ];
        $this->assertSame(
            $current_configuration,
            $this->class->getSettingsSection($current_configuration)
        );
    }

    public function testWhenNoLoggedGiven()
    {
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'mode' => 1,
        ];

        $this->assertSame(
            [
                'email' => 'unit.test@payplug.com',
                'logged' => false,
                'mode' => 1,
            ],
            $this->class->getSettingsSection($current_configuration)
        );
    }

    /**
     * @description  check if psaccount is not connected for pspl
     */
    public function testWhenPsaccountIsntConnected()
    {
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'logged' => false,
            'mode' => 1,
        ];
        $this->assertSame(
            $current_configuration,
            $this->class->getSettingsSection($current_configuration)
        );
    }

    /**
     * @description  check when psaccount is connected for pspl
     */
    public function testWhenPsaccountIsConnected()
    {
        $current_configuration = [
            'email' => 'unit.test@payplug.com',
            'logged' => false,
            'mode' => 1,
        ];
        $this->assertSame(
            $current_configuration,
            $this->class->getSettingsSection($current_configuration)
        );
    }

    /**
     * @description check that logged must be false when psaccount is disconnected
     */
    public function testNotLoggedWhenPsaccountIsntConnected()
    {
        $configurationClass = \Mockery::mock('ConfigurationClass');
        $current_configuration = [
        ];
        $configurationClass->shouldReceive('getDefault')
            ->with('email')
            ->andReturn('unit.test@payplug.com');
        $configurationClass->shouldReceive('getDefault')
            ->with('sandbox_mode')
            ->andReturn('1');
        $this->assertFalse(
            $this->class->getSettingsSection($current_configuration)['logged']
        );
    }
}
