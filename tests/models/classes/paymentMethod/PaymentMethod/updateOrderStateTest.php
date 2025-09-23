<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class updateOrderStateTest extends BasePaymentMethod
{
    public $order_id;
    public $order;
    public $order_adapter;
    public $order_class;

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
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsntValidInteger($order_id)
    {
        $this->assertFalse(
            $this->class->updateOrderStateFromPaidToRefund($order_id)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_live
     */
    public function testWhenGivenIsLiveIsntValidBoolean($is_live)
    {
        $this->assertFalse(
            $this->class->updateOrderStateFromPaidToRefund($this->order_id, $is_live)
        );
    }

    public function testWhenOrderGotIsNotValid()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertFalse(
            $this->class->updateOrderStateFromPaidToRefund($this->order_id)
        );
    }

    public function testWhenOrderStatusIsNotPaid()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('order_state_paid');
        $this->order->shouldReceive([
            'getCurrentState' => 'other_order_state',
        ]);

        $this->assertTrue(
            $this->class->updateOrderStateFromPaidToRefund($this->order_id)
        );
    }

    public function testWhenOrderStateCantBeUpdated()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('order_state_paid');
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_refund')
            ->andReturn('order_state_refund');
        $this->order->shouldReceive([
            'getCurrentState' => 'order_state_paid',
        ]);
        $this->order_class->shouldReceive([
            'updateOrderState' => false,
        ]);

        $this->assertFalse(
            $this->class->updateOrderStateFromPaidToRefund($this->order_id)
        );
    }

    public function testWhenOrderStateIsUpdated()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('order_state_paid');
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_refund')
            ->andReturn('order_state_refund');
        $this->order->shouldReceive([
            'getCurrentState' => 'order_state_paid',
        ]);
        $this->order_class->shouldReceive([
            'updateOrderState' => true,
        ]);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturn('reload');

        $this->assertSame(
            'reload',
            $this->class->updateOrderStateFromPaidToRefund($this->order_id)
        );
    }
}
