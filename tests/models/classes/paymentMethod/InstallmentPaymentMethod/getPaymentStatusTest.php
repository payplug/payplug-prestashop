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
    private $api_service;

    public function setUp()
    {
        parent::setUp();
        $this->api_service = \Mockery::mock('ApiService');
        $this->plugin->shouldReceive([
            'getApiService' => $this->api_service,
        ]);
    }

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
        $schedule = PaymentMock::getStandard();
        $this->api_service->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => $schedule,
            ],
        ]);
        $resource = PaymentMock::getInstallment($parameters);
        $this->assertSame(
            $expected,
            $this->class->getPaymentStatus($resource)
        );
    }
}
