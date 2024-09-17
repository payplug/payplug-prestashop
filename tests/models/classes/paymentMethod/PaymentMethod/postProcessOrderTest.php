<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_classe
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class postProcessOrderTest extends BasePaymentMethod
{
    private $order_adapter;

    public function setUp()
    {
        parent::setUp();
        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->order_adapter->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $this->plugin->shouldReceive([
            'getOrder' => $this->order_adapter,
        ]);
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsntValidObject($resource)
    {
        $id_order = 42;
        $this->assertFalse($this->class->postProcessOrder($resource, $id_order));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order
     */
    public function testWhenGivenOrderIsntValidInterger($id_order)
    {
        $resource = PaymentMock::getStandard();
        $this->assertFalse($this->class->postProcessOrder($resource, $id_order));
    }

    public function testWhenRelativeOrderCantBeRetrieved()
    {
        $id_order = 42;
        $resource = PaymentMock::getStandard();
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertFalse($this->class->postProcessOrder($resource, $id_order));
    }

    public function testWhenResourceCannotBePatched()
    {
        $id_order = 42;
        $resource = PaymentMock::getStandard();
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->api_service->shouldReceive([
            'patchPayment' => [
                'result' => false,
            ],
        ]);

        $this->assertFalse($this->class->postProcessOrder($resource, $id_order));
    }

    public function testWhenOrderIsPostProcessed()
    {
        $id_order = 42;
        $resource = PaymentMock::getStandard();
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->api_service->shouldReceive([
            'patchPayment' => [
                'result' => true,
                'resource' => $resource,
            ],
        ]);

        $this->assertTrue($this->class->postProcessOrder($resource, $id_order));
    }
}
