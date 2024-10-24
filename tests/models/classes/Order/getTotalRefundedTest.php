<?php

namespace PayPlug\tests\models\classes\Order;

use PayPlug\tests\mock\OrderMock;

/**
 * @group unit
 * @group class
 * @group order_classe
 *
 * @runTestsInSeparateProcesses
 */
class getTotalRefundedTest extends BaseOrder
{
    private $id_order;
    private $order_adapter;
    private $order_slip_adapter;
    private $validate_adapter;

    public function setUp()
    {
        parent::setUp();
        $this->id_order = 42;
        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->order_adapter->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $this->order_slip_adapter = \Mockery::mock('OrderSlipAdapter');
        $this->validate_adapter = \Mockery::mock('ValidateAdapter');
        $this->plugin->shouldReceive([
            'getOrder' => $this->order_adapter,
            'getOrderSlip' => $this->order_slip_adapter,
            'getValidate' => $this->validate_adapter,
        ]);
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order
     */
    public function testWhenGivenIdOrderIsInvalidIntegerFormat($id_order)
    {
        $this->assertSame(0, $this->class->getTotalRefunded($id_order));
    }

    public function testWhenGettedOrderIsNotValidate()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertSame(0, $this->class->getTotalRefunded($this->id_order));
    }

    public function testWhenNoOrderSlipFound()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->order_slip_adapter->shouldReceive([
            'getOrdersSlip' => [],
        ]);
        $this->assertSame(0, $this->class->getTotalRefunded($this->id_order));
    }

    public function testWhenOrderSlipReturned()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->order_slip_adapter->shouldReceive([
            'getOrdersSlip' => [
                [
                    'amount' => 42,
                    'shipping_cost' => 1,
                    'shipping_cost_amount' => 42,
                ],
            ],
        ]);
        $this->assertSame(84, $this->class->getTotalRefunded($this->id_order));
    }
}
