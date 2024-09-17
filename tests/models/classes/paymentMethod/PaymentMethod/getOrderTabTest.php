<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

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
class getOrderTabTest extends BasePaymentMethod
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

    public function testWhenPaymentResourceIsPaid()
    {
        $retrieve = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_pending')
            ->andReturn('6');
        $expected = [
            'order_state' => '6',
            'amount' => 313.2,
            'module_name' => 'order.module.default',
        ];
        $this->assertSame($expected, $this->class->getOrderTab($retrieve));
    }

    public function testWhenTabIsPaidReturn()
    {
        $retrieve = [
            'result' => true,
            'resource' => PaymentMock::getStandard(['is_paid' => true]),
        ];
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('2');

        $expected = [
            'order_state' => '2',
            'amount' => 313.2,
            'module_name' => 'order.module.default',
        ];
        $this->assertSame($expected, $this->class->getOrderTab($retrieve));
    }
}
