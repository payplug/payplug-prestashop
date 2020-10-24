<?php
/**
 * 2013 - 2020 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

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
    public function isStandardPayment() {
        $payment = PaymentMock::getStandardPayment();
        $this->assertTrue($payment instanceof \Payplug\Resource\Payment, 'is Payment resource');
        $this->assertFalse($payment instanceof \Payplug\Resource\Refund, 'is not Refund resource');
        $this->assertFalse($payment instanceof \Payplug\Resource\InstallmentPlan, 'is not InstallmentPlan resource');
    }

    /**
     * @test
     */
    public function isInstallmentPlanPayment() {
        $payment = PaymentMock::getStandardPayment();
        $this->assertNotNull($payment->installment_plan_id, 'The payment is not first schedule of installment plan');
    }

    /**
     * @test
     */
    public function isDeferredPayment() {
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
