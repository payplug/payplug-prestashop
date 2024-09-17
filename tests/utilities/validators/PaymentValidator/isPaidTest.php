<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\mock\PaymentMock;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class isPaidTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidObjectFormatDataProvider()
    {
        yield ['lorem Ipsum'];
        yield [true];
        yield [42];
        yield [['key' => 'value']];
        yield [null];
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment
     */
    public function testWhenPaymentGivenIsInvalidFormat($payment)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $payment must be a non null object',
        ], $this->validator->isPaid($payment));
    }

    public function testWhenPaymentPropIsPaidIsMissing()
    {
        $payment = new \stdClass();
        $payment->id = 'pay_5ktNvd3BNCp6GPcqIZvY9j';
        $payment->object = 'payment';

        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment does not contain is_paid props',
        ], $this->validator->isPaid($payment));
    }

    public function testWhenPaymentIsntPaid()
    {
        $parameters = [
            'is_paid' => false,
        ];
        $payment = PaymentMock::getStandard($parameters);

        $this->assertSame([
            'result' => false,
            'message' => 'Payment is not paid',
        ], $this->validator->isPaid($payment));
    }

    public function testWhenPaymentIsPaid()
    {
        $parameters = [
            'is_paid' => true,
        ];
        $payment = PaymentMock::getStandard($parameters);

        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isPaid($payment));
    }
}
