<?php

namespace PayPlug\tests\actions\OrderStateAction;

/**
 * @group unit
 * @group action
 * @group order_state_action
 *
 * @runTestsInSeparateProcesses
 */
class deleteTypeActionTest extends BaseOrderStateAction
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order_state
     */
    public function testWhenGivenPaymentIsInvalidIntegerFormat($id_order_state)
    {
        $this->assertFalse($this->action->deleteTypeAction($id_order_state));
    }

    public function testWhenOrderStateTypeCannotBeDelete()
    {
        $id_order_state = 42;

        $this->payplug_orderstate_repository->shouldReceive([
            'deleteBy' => false,
        ]);

        $this->assertFalse($this->action->deleteTypeAction($id_order_state));
    }

    public function testWhenOrderStateTypeIsDeleted()
    {
        $id_order_state = 42;

        $this->payplug_orderstate_repository->shouldReceive([
            'deleteBy' => true,
        ]);

        $this->assertTrue($this->action->deleteTypeAction($id_order_state));
    }
}
