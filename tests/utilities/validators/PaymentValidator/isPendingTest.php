<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class isPendingTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidArrayFormatDataProvider()
    {
        yield ['lorem Ipsum'];
        yield [true];
        yield [42];
        yield [[]];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment
     */
    public function testWithInvalidPaymentFormat($payment)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument given, $payment must be a non empty array',
            ],
            $this->validator->isPending($payment)
        );
    }

    public function testWithPaymentArrayKeyIsPendingIsMissingFormat()
    {
        $payment = [
            'id_payplug_payment' => '',
            'id_payment' => 'pay_2az3e4r5t6y7u8i9',
            'payment_method' => 'standard',
            'id_cart' => 42,
            'is_paid' => true,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Missing props, $payment does not contain is_pending',
            ],
            $this->validator->isPending($payment)
        );
    }

    public function testWithPaymentArrayKeyIsPendingIsFalseFormat()
    {
        $payment = [
            'id_payplug_payment' => '',
            'id_payment' => 'pay_2az3e4r5t6y7u8i9',
            'payment_method' => 'standard',
            'id_cart' => 42,
            'is_paid' => true,
            'is_pending' => false,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given payment is not pending',
            ],
            $this->validator->isPending($payment)
        );
    }

    public function testWithPaymentArrayKeyIsPendingIsTrueFormat()
    {
        $payment = [
            'id_payplug_payment' => '',
            'id_payment' => 'pay_2az3e4r5t6y7u8i9',
            'payment_method' => 'standard',
            'id_cart' => 42,
            'is_paid' => true,
            'is_pending' => true,
        ];
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->validator->isPending($payment)
        );
    }
}
