<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getOrderTabTest extends BaseOneyPaymentMethod
{
    public $retrieve;
    public $order_tab;
    public $id_order_state_pending;

    public function setUp()
    {
        parent::setUp();

        $this->order_tab = [
            'order_state' => '77',
            'amount' => 172.32,
            'module_name' => 'Payplug',
        ];
        $this->id_order_state_pending = 42;
        $this->class->shouldReceive([
            'getParentOrderTab' => $this->order_tab,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_oney_pg')
            ->andReturn($this->id_order_state_pending);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenRetrieveIsntValidArray($current_configuration)
    {
        $this->assertSame([], $this->class->getOrderTab($current_configuration));
    }

    public function testWhenOrderIsNotPaid()
    {
        $retrieve = [
            'resource' => PaymentMock::getOney(['is_paid' => false]),
        ];

        $expected = $this->order_tab;
        $expected['order_state'] = $this->id_order_state_pending;
        $expected['module_name'] = 'order.module.oney.x3_with_fees';
        $this->assertSame(
            $expected,
            $this->class->getOrderTab($retrieve)
        );
    }

    public function testWhenOrderIsPaid()
    {
        $retrieve = [
            'resource' => PaymentMock::getOney(['is_paid' => true]),
        ];

        $expected = $this->order_tab;
        $expected['module_name'] = 'order.module.oney.x3_with_fees';
        $this->assertSame(
            $expected,
            $this->class->getOrderTab($retrieve)
        );
    }
}
