<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class installOrderStateActionTest extends BaseConfigurationAction
{
    private $order_state;

    public function setUp()
    {
        parent::setUp();
        $this->order_state = \Mockery::mock('OrderState');
        $this->plugin
            ->shouldReceive([
                'getOrderState' => $this->order_state,
            ]);
    }

    public function testWhenOrderStateCantBeCreated()
    {
        $this->order_state
            ->shouldReceive([
                'create' => false,
            ]);
        $this->assertFalse($this->action->installOrderStateAction());
    }

    public function testWhenUnusedOrderStateCantBeRemoved()
    {
        $this->order_state
            ->shouldReceive([
                'create' => true,
                'removeIdsUnusedByPayPlug' => false,
            ]);
        $this->assertFalse($this->action->installOrderStateAction());
    }

    public function testWhenOrderStatesAreInstalled()
    {
        $this->order_state
            ->shouldReceive([
                'create' => true,
                'removeIdsUnusedByPayPlug' => true,
            ]);
        $this->assertTrue($this->action->installOrderStateAction());
    }
}
