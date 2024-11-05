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
class isPaymentTest extends TestCase
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
        ], $this->validator->isPayment($payment));
    }

    public function testWhenPaymentGivenIsInvalidId()
    {
        $payment = new \stdClass();
        $payment->id = null;

        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment does not valid id',
        ], $this->validator->isPayment($payment));
    }

    public function testWhenPaymentGivenIsInvalidAmount()
    {
        $payment = new \stdClass();
        $payment->id = 1234;
        $payment->amount = null;

        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment does not valid amount nor authorized_amount',
        ], $this->validator->isPayment($payment));
    }

    public function testWhenPaymentGivenIsInvalidAuthorizedAmount()
    {
        $payment = new \stdClass();
        $payment->id = 1234;
        $payment->authorized_amount = null;

        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment does not valid amount nor authorized_amount',
        ], $this->validator->isPayment($payment));
    }

    public function testWhenPaymentGivenIsValid()
    {
        $payment = new \stdClass();
        $payment->id = 1234;
        $payment->amount = 1234;
        $payment->authorized_amount = 1234;

        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isPayment($payment));
    }
}
