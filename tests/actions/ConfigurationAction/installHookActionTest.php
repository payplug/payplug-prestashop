<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @runTestsInSeparateProcesses
 */
class installHookActionTest extends BaseConfigurationAction
{
    public function testWhenNoHookListReturned()
    {
        $module = \Mockery::mock('Module');
        $module->shouldReceive([
            'getHookList' => [],
        ]);
        $this->module->shouldReceive([
            'getInstanceByName' => $module,
        ]);
        $this->assertTrue($this->action->installHookAction());
    }

    public function testWhenHookCantBeRegistered()
    {
        $module = \Mockery::mock('Module');
        $module->shouldReceive([
            'getHookList' => [
                'hook_name',
            ],
            'registerHook' => false,
        ]);
        $this->module->shouldReceive([
            'getInstanceByName' => $module,
        ]);
        $this->assertFalse($this->action->installHookAction());
    }

    public function testWhenHookIsRegistered()
    {
        $module = \Mockery::mock('Module');
        $module->shouldReceive([
            'getHookList' => [
                'hook_name',
            ],
            'registerHook' => true,
        ]);
        $this->module->shouldReceive([
            'getInstanceByName' => $module,
        ]);
        $this->assertTrue($this->action->installHookAction());
    }
}
