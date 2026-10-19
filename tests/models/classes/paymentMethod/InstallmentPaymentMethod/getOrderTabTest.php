<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 */
class getOrderTabTest extends BaseInstallmentPaymentMethod
{
    public function testAmountIsConvertedInTheInstallmentPlanCurrency()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('2');

        $order_tab = $this->class->getOrderTab([
            'resource' => PaymentMock::getInstallment(['currency' => 'JPY']),
            'schedule' => [
                ['resource' => PaymentMock::getStandard(), 'amount' => 10440],
                ['resource' => null, 'amount' => 10440],
            ],
        ]);

        $this->assertSame('2', $order_tab['order_state']);
        $this->amountHelper->shouldHaveReceived('convertAmount')
            ->with(20880, true, 'JPY')
            ->once();
    }
}
