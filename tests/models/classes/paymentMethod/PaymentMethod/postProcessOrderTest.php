<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class postProcessOrderTest extends BasePaymentMethod
{
    private $order_adapter;
    private $retrieve;
    private $id_order;

    public function setUp()
    {
        parent::setUp();
        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->order_adapter->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $this->id_order = 42;
        $this->retrieve = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->plugin->shouldReceive([
            'getOrder' => $this->order_adapter,
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $retrieve
     */
    public function testWhenGivenResourceIsntValidArray($retrieve)
    {
        $this->assertFalse($this->class->postProcessOrder($retrieve, $this->id_order));
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsntValidObject($resource)
    {
        $retrieve = [
            'resource' => $resource,
        ];
        $this->assertFalse($this->class->postProcessOrder($retrieve, $this->id_order));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order
     */
    public function testWhenGivenOrderIsntValidInterger($id_order)
    {
        $this->assertFalse($this->class->postProcessOrder($this->retrieve, $id_order));
    }

    public function testWhenRelativeOrderCantBeRetrieved()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertFalse($this->class->postProcessOrder($this->retrieve, $this->id_order));
    }

    public function testWhenResourceCannotBePatched()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->api_service->shouldReceive([
            'patchPayment' => [
                'result' => false,
            ],
        ]);

        $this->assertFalse($this->class->postProcessOrder($this->retrieve, $this->id_order));
    }

    public function testWhenOrderIsPostProcessed()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->api_service->shouldReceive([
            'patchPayment' => $this->retrieve,
        ]);

        $this->assertTrue($this->class->postProcessOrder($this->retrieve, $this->id_order));
    }
}
