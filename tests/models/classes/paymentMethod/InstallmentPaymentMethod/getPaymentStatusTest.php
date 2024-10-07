<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use Payplug\Payment;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentStatusTest extends BaseInstallmentPaymentMethod
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsntValidObjectFormat($resource)
    {
        $this->assertSame([], $this->class->getPaymentStatus($resource));
    }

    public function testWhenInstallmentIsActive()
    {
        $resource = PaymentMock::getInstallment(['is_active' => true]);
        $this->assertSame(
            [
                'id_status' => 6,
                'code' => 'on_going',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }

    public function testWhenInstallmentIsPaid()
    {
        $resource = PaymentMock::getInstallment(['is_active' => false, 'is_paid' => true]);
        $expected = [
            'id_status' => 2,
            'code' => 'paid',
        ];
        $this->assertSame(
            $expected,
            $this->class->getPaymentStatus($resource)
        );
    }
}
