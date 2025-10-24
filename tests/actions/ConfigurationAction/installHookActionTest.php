<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class installHookActionTest extends BaseConfigurationAction
{
    public function testWhenNoHookListReturned()
    {
        $this->module->shouldReceive([
            'getHookList' => [],
        ]);
        $this->assertTrue($this->action->installHookAction());
    }

    public function testWhenHookCantBeRegistered()
    {
        $this->module->shouldReceive([
            'getHookList' => [
                'hook_name',
            ],
            'registerHook' => false,
        ]);
        $this->assertFalse($this->action->installHookAction());
    }

    public function testWhenHookIsRegistered()
    {
        $this->module->shouldReceive([
            'getHookList' => [
                'hook_name',
            ],
            'registerHook' => true,
        ]);
        $this->assertTrue($this->action->installHookAction());
    }
}
