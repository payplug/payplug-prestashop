<?php

namespace PayPlugTest;

use PayPlugMock\PaymentMock;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group ci
 * @group recommended
 */
class PayPlugNotificationsTest extends TestCase
{
    /**
     * @test
     */
    public function isStandardPayment()
    {
        $payment = PaymentMock::getStandardPayment();
        $this->assertTrue($payment instanceof \Payplug\Resource\Payment, 'is Payment resource');
        $this->assertFalse($payment instanceof \Payplug\Resource\Refund, 'is not Refund resource');
        $this->assertFalse($payment instanceof \Payplug\Resource\InstallmentPlan, 'is not InstallmentPlan resource');
    }

    /**
     * @test
     */
    public function isInstallmentPlanPayment()
    {
        $payment = PaymentMock::getStandardPayment();
        $this->assertNotNull($payment->installment_plan_id, 'The payment is not first schedule of installment plan');
    }

    /**
     * @test
     */
    public function isDeferredPayment()
    {
        $payment = PaymentMock::getStandardPayment();
        $this->assertNotNull($payment->authorization);
    }

//    /**
//     * @test
//     */
//    public function isOneyPayment() {
//        $payment = PaymentMock::getStandardPayment();
//
//        $oney_type = [
//            'oney_x3_with_fees',
//            'oney_x4_with_fees'
//        ];
//        $this->assertObjectHasAttribute('payment_method',$payment);
//        $this->assertArrayHasKey('type',$payment->payment_method);
//
//        $payment->payment_method['type']
//
//        if (isset($payment->payment_method) && isset($payment->payment_method['type'])) {
//            switch () {
//                case 'oney_x3_with_fees':
//                case 'oney_x4_with_fees':
//                    $is_oney = true;
//                    break;
//                default:
//                    $is_oney = false;
//            }
//        }
//    }
}
