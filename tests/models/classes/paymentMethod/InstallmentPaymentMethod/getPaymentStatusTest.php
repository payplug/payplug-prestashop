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

    public function testWhenGivenResourceIsActive()
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

    public function testWhenGivenResourceHasAbortedFailure()
    {
        $parameters = [
            'is_active' => false,
            'failure' => [
                'code' => 'aborted',
            ],
        ];
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            [
                'id_status' => 7,
                'code' => 'cancelled',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }

    public function testWhenGivenResourceHasTimeoutFailure()
    {
        $parameters = [
            'is_active' => false,
            'failure' => [
                'code' => 'timeout',
            ],
        ];
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            [
                'id_status' => 11,
                'code' => 'abandoned',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }

    public function testWhenGivenResourceHasFailure()
    {
        $parameters = [
            'is_active' => false,
            'failure' => [
                'code' => 'failed',
            ],
        ];
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            [
                'id_status' => 3,
                'code' => 'failed',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }

    public function testWhenGivenResourceIsFullyRefunded()
    {
        $parameters = [
            'is_active' => false,
            'schedule' => [
                0 => [
                    'date' => '2021-03-05',
                    'amount' => 4242,
                    'payment_ids' => [
                        0 => 'pay_azerty12345',
                    ],
                ],
            ],
        ];
        $schedule = PaymentMock::getStandard([
            'amount_refunded' => 4242,
        ]);
        $this->api_service->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => $schedule,
            ],
        ]);
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            [
                'id_status' => 5,
                'code' => 'refunded',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }

    public function testWhenGivenResourceIsPartiallyRefunded()
    {
        $parameters = [
            'is_active' => false,
            'schedule' => [
                0 => [
                    'date' => '2021-03-05',
                    'amount' => 4242,
                    'payment_ids' => [
                        0 => 'pay_azerty12345',
                    ],
                ],
            ],
        ];
        $schedule = PaymentMock::getStandard([
            'amount_refunded' => 100,
        ]);
        $this->api_service->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => $schedule,
            ],
        ]);
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            [
                'id_status' => 4,
                'code' => 'partially_refunded',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }

    public function testWhenGivenResourceIsFullyPaid()
    {
        $parameters = [
            'is_active' => false,
            'is_fully_paid' => true,
        ];
        $schedule = PaymentMock::getStandard();
        $this->api_service->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => $schedule,
            ],
        ]);
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            [
                'id_status' => 2,
                'code' => 'paid',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }

    public function testWhenGivenResourceIsNotPaid()
    {
        $parameters = [
            'is_active' => false,
            'is_fully_paid' => false,
        ];
        $schedule = PaymentMock::getStandard();
        $this->api_service->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => $schedule,
            ],
        ]);
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            [
                'id_status' => 1,
                'code' => 'not_paid',
            ],
            $this->class->getPaymentStatus($resource)
        );
    }
}
