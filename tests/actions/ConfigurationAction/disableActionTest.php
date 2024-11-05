<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @runTestsInSeparateProcesses
 */
class disableActionTest extends BaseConfigurationAction
{
    public function testWhenConfigurationCantBeSetted()
    {
        $this->configuration_class->shouldReceive([
            'set' => false,
        ]);
        $this->assertSame(
            false,
            $this->action->disableAction()
        );
    }

    public function testWhenConfigurationIsSettedTrue()
    {
        $this->configuration_class->shouldReceive([
            'set' => true,
        ]);
        $this->assertSame(
            true,
            $this->action->disableAction()
        );
    }
}
