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

    public function invalidArrayFormatDataProvider()
    {
        yield [42];
        yield [null];
        yield [false];
        yield ['lorem ipsum'];
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
        $current_configuration = [
            'email' => '',
            'logged' => true,
            'mode' => 1,
        ];
        $this->assertSame(
            $current_configuration,
            $this->classe->getSettingsSection($current_configuration)
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
            $this->classe->getSettingsSection($current_configuration)
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
            $this->classe->getSettingsSection($current_configuration)
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
            $this->classe->getSettingsSection($current_configuration)
        );
    }
}
