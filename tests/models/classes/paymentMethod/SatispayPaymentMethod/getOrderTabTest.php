<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group satispay_payment_method_class
 */
class getOrderTabTest extends BaseSatispayPaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $retrieve
     */
    public function testWhenGivenRetrieveIsntValidArray($retrieve)
    {
        $this->assertSame([], $this->class->getOrderTab($retrieve));
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenRetrieveResourceIsntValidObject($resource)
    {
        $retrieve = [
            'result' => true,
            'resource' => $resource,
        ];
        $this->assertSame([], $this->class->getOrderTab($retrieve));
    }

    /**
     * @description test the behavior of the getOrderTab()
     * method when the order state is marked as pending
     */
    public function testWhenOrderStateIsPending()
    {
        $this->class->set('name', 'satispay');
        $retrieve = [
            'result' => true,
            'resource' => PaymentMock::getSatispay(),
        ];
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_pending')
            ->andReturn('6');
        $expected = [];

        $this->assertSame($expected, $this->class->getOrderTab($retrieve));
    }

    /**
     * @description test the behavior of the getOrderTab()
     * method when the order state is marked as paid.
     */
    public function testWhenOrderStateIsPaid()
    {
        $this->class->set('name', 'satispay');
        $retrieve = [
            'result' => true,
            'resource' => PaymentMock::getSatispay(['is_paid' => true]),
        ];
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('2');
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_pending')
            ->andReturn('6');
        $expected = [
            'order_state' => '2',
            'amount' => 313.2,
            'module_name' => 'order.module.satispay',
        ];

        $this->assertSame($expected, $this->class->getOrderTab($retrieve));
    }
}
