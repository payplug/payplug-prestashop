<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOrderTabTest extends BaseSatispayPaymentMethod
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsntValidObject($resource)
    {
        $this->assertSame([], $this->classe->getOrderTab($resource));
    }

    /**
     * @description test the behavior of the getOrderTab()
     * method when the order state is marked as pending
     */
    public function testWhenOrderStateIsPending()
    {
        $this->classe->set('name', 'satispay');
        $resource = PaymentMock::getSatispay();
        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_pending')
            ->andReturn('6');
        $expected = [];

        $this->assertSame($expected, $this->classe->getOrderTab($resource));
    }

    /**
     * @description test the behavior of the getOrderTab()
     * method when the order state is marked as paid.
     */
    public function testWhenOrderStateIsPaid()
    {
        $this->classe->set('name', 'satispay');
        $resource = PaymentMock::getSatispay();
        $resource->is_paid = true;
        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('2');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_pending')
            ->andReturn('6');
        $expected = [
            'order_state' => '2',
            'amount' => 313.2,
            'module_name' => 'order.module.satispay',
        ];

        $this->assertSame($expected, $this->classe->getOrderTab($resource));
    }
}
