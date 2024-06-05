<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getOrderTabTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsntValidObject($resource)
    {
        $this->assertSame([], $this->classe->getOrderTab($resource));
    }

    public function testWhenTabIsReturn()
    {
        $resource = PaymentMock::getStandard();

        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_pending')
            ->andReturn('6');
        $expected = [
            'order_state' => '6',
            'amount' => 313.2,
            'module_name' => 'order.module.default',
        ];
        $this->assertSame($expected, $this->classe->getOrderTab($resource));
    }

    public function testWhenTabIsPaidReturn()
    {
        $resource = PaymentMock::getStandard();
        $resource->is_paid = true;
        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('2');

        $expected = [
            'order_state' => '2',
            'amount' => 313.2,
            'module_name' => 'order.module.default',
        ];
        $this->assertSame($expected, $this->classe->getOrderTab($resource));
    }
}
