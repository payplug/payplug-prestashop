<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOrderTabTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsNotValidObject($resource)
    {
        $this->assertSame([], $this->classe->getOrderTab($resource));
    }

    public function testWhenTabIsReturn()
    {
        $resource = PaymentMock::getStandard();
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
