<?php

namespace PayPlug\tests\models\classes\Order;

use PayPlug\tests\mock\OrderMock;

/**
 * @group unit
 * @group class
 * @group order_class
 */
class updateOrderStateTest extends BaseOrder
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $order
     */
    public function testWhenGivenOrderIsInvalidObjectFormat($order)
    {
        $new_order_state = 42;
        $this->assertFalse($this->class->updateOrderState($order, $new_order_state));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $new_order_state
     */
    public function testWhenGivenNewOrderStateIsInvalidIntegerFormat($new_order_state)
    {
        $order = OrderMock::get();
        $this->assertFalse($this->class->updateOrderState($order, $new_order_state));
    }

    public function testWhenCurrentStateAndNewStateAreTheSame()
    {
        $order = \Mockery::mock('Order');
        $order->id = 1;
        $order->current_state = 42;
        $order->shouldReceive([
            'getCurrentState' => $order->current_state,
        ]);
        $new_order_state = $order->current_state;
        $this->assertTrue($this->class->updateOrderState($order, $new_order_state));
    }

    public function testWhenOrderHistoryCannotBeSaved()
    {
        $order_history = \Mockery::mock('OrderHistory');
        $order_history->shouldReceive([
            'changeIdOrderState' => true,
            'save' => false,
            'addWithemail' => true,
        ]);
        $order_history_adapter = \Mockery::mock('OrderHistoryAdapter');
        $order_history_adapter->shouldReceive([
            'get' => $order_history,
        ]);

        $this->plugin->shouldReceive([
            'getOrderHistory' => $order_history_adapter,
        ]);

        $order = \Mockery::mock('Order');
        $order->id = 1;
        $order->current_state = 42;
        $order->shouldReceive([
            'getCurrentState' => $order->current_state,
        ]);
        $new_order_state = 1;
        $this->assertFalse($this->class->updateOrderState($order, $new_order_state));
    }

    public function testWhenOrderIsUpdated()
    {
        $order_history = \Mockery::mock('OrderHistory');
        $order_history->id_order_state = 42;
        $order_history->shouldReceive([
            'changeIdOrderState' => true,
            'save' => true,
            'addWithemail' => true,
        ]);
        $order_history_adapter = \Mockery::mock('OrderHistoryAdapter');
        $order_history_adapter->shouldReceive([
            'get' => $order_history,
        ]);

        $order = \Mockery::mock('Order');
        $order->id = 1;
        $order->current_state = 42;
        $order->shouldReceive([
            'getCurrentState' => $order->current_state,
        ]);

        $this->plugin->shouldReceive([
            'getOrderHistory' => $order_history_adapter,
        ]);

        $new_order_state = 1;
        $this->assertTrue($this->class->updateOrderState($order, $new_order_state));
    }
}
