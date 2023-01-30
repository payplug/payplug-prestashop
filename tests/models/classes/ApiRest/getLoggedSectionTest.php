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
            $this->classe->getLoggedSection($current_configuration)
        );
    }

    public function testWhenNoSandboxModeIsGiven()
    {
        $current_configuration = [];
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
}
