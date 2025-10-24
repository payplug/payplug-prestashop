<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group oney_payment_method_class
 * @group parent_oney_payment_method_classe
 */
class updateOrderStateFromPendingToPaidTest extends BaseOneyPaymentMethod
{
    private $order_id;
    private $order;
    private $order_adapter;
    private $order_class;

    public function setUp()
    {
        parent::setUp();

        $this->order_id = 42;
        $this->order = \Mockery::mock('Order');
        $this->order->id = $this->order_id;
        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->order_class = \Mockery::mock('OrderClass');
        $this->order_adapter->shouldReceive([
            'get' => $this->order,
        ]);
        $this->plugin->shouldReceive([
            'getOrder' => $this->order_adapter,
            'getOrderClass' => $this->order_class,
        ]);
    }

    /**
     * Test that updateOrderStateFromPendingToPaid returns false when the given order ID is not a valid integer.
     *
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsntValidInteger($order_id)
    {
        $this->assertFalse(
            $this->class->updateOrderStateFromPendingToPaid($order_id)
        );
    }

    /**
     * Test that updateOrderStateFromPendingToPaid returns false when the given is_live is not a valid boolean.
     *
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_live
     */
    public function testWhenGivenIsLiveIsntValidBoolean($is_live)
    {
        $this->assertFalse(
            $this->class->updateOrderStateFromPendingToPaid($this->order_id, $is_live)
        );
    }

    /**
     * Test that updateOrderStateFromPendingToPaid returns false when order is not found (validate fails).
     */
    public function testWhenOrderIsNotFound()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertFalse(
            $this->class->updateOrderStateFromPendingToPaid($this->order_id)
        );
    }

    /**
     * Test that updateOrderStateFromPendingToPaid returns true when order status is not pending.
     */
    public function testWhenOrderStatusIsNotPending()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_oney_pg')
            ->andReturn('order_state_oney_pg');
        $this->order->shouldReceive([
            'getCurrentState' => 'other_order_state',
        ]);
        $this->configuration_adapter->shouldReceive('get')->andReturn('other_order_state');

        $this->assertTrue(
            $this->class->updateOrderStateFromPendingToPaid($this->order_id)
        );
    }

    /**
     * Test that updateOrderStateFromPendingToPaid returns true when order status is pending and update succeeds.
     */
    public function testWhenOrderStatusIsPending()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_oney_pg')
            ->andReturn('order_state_oney_pg');

        $this->order->shouldReceive([
            'getCurrentState' => 'other_order_state',
        ]);
        $this->order_class->shouldReceive([
            'updateOrderState' => true,
        ]);
        $this->assertTrue(
            $this->class->updateOrderStateFromPendingToPaid($this->order_id)
        );
    }

    /**
     * Test that updateOrderStateFromPendingToPaid returns false and logs an error when order status is pending but update fails.
     */
    public function testWhenOrderStatusIsPendingButUpdateFails()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_oney_pg')
            ->andReturn('order_state_oney_pg');
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('order_state_paid');
        $this->order->shouldReceive([
            'getCurrentState' => 'order_state_oney_pg',
        ]);
        $this->order_class->shouldReceive([
            'updateOrderState' => false,
        ]);
        $this->assertFalse(
            $this->class->updateOrderStateFromPendingToPaid($this->order_id)
        );
    }
}
