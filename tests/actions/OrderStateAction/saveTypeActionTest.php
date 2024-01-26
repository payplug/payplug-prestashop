<?php

namespace PayPlug\tests\actions\OrderStateAction;

/**
 * @group unit
 * @group action
 * @group order_state_action
 *
 * @runTestsInSeparateProcesses
 */
class saveTypeActionTest extends BaseOrderStateAction
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order_state
     */
    public function testWhenGivenPaymentIsInvalidIntegerFormat($id_order_state)
    {
        $type = 'order_state_type';
        $this->assertFalse($this->action->saveTypeAction($id_order_state, $type));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $id_order_state
     * @param mixed $type
     */
    public function testWhenGivenTypeIsInvalidStringFormat($type)
    {
        $id_order_state = 42;
        $this->assertFalse($this->action->saveTypeAction($id_order_state, $type));
    }

    public function testWhenOrderStateCantBeRetrieved()
    {
        $id_order_state = 42;
        $type = 'order_state_type';

        $order_state = \Mockery::mock('OrderState');
        $this->order_state_adapter
            ->shouldReceive([
                'get' => $order_state,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => false,
            ]);

        $this->assertFalse($this->action->saveTypeAction($id_order_state, $type));
    }

    public function testWhenOrderStateCantBeDeleted()
    {
        $id_order_state = 42;
        $type = 'order_state_type';

        $order_state = \Mockery::mock('OrderState');
        $order_state->deleted = true;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $order_state,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->action
            ->shouldReceive([
                'deleteTypeAction' => false,
            ]);

        $this->assertFalse($this->action->saveTypeAction($id_order_state, $type));
    }

    public function testWhenOrderStateTypeCanBeAdded()
    {
        $id_order_state = 42;
        $type = 'order_state_type';

        $order_state = \Mockery::mock('OrderState');

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $order_state,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_orderstate_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => false,
                'setOrderState' => false,
            ]);

        $this->assertFalse($this->action->saveTypeAction($id_order_state, $type));
    }

    public function testWhenOrderStateTypeIsAdded()
    {
        $id_order_state = 42;
        $type = 'order_state_type';

        $order_state = \Mockery::mock('OrderState');

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $order_state,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_orderstate_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => false,
                'setOrderState' => true,
            ]);

        $this->assertTrue($this->action->saveTypeAction($id_order_state, $type));
    }

    public function testWhenOrderStateTypeCanBeUpdated()
    {
        $id_order_state = 42;
        $type = 'order_state_type';

        $order_state = \Mockery::mock('OrderState');

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $order_state,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_orderstate_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => 42,
                'updateByOderState' => false,
            ]);

        $this->assertFalse($this->action->saveTypeAction($id_order_state, $type));
    }

    public function testWhenOrderStateTypeIsUpdated()
    {
        $id_order_state = 42;
        $type = 'order_state_type';

        $order_state = \Mockery::mock('OrderState');

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $order_state,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_orderstate_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => 42,
                'updateByOderState' => true,
            ]);

        $this->assertTrue($this->action->saveTypeAction($id_order_state, $type));
    }
}
