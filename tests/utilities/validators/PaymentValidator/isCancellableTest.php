<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class isCancellableTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $payment_method
     */
    public function testWithInvalidPaymentMethodFormat($payment_method)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $payment_method must be a non empty string',
        ], $this->validator->isCancellable($payment_method));
    }

    public function testWhenPaymentMethodIsntCancellable()
    {
        $payment_method = 'oney';
        $this->assertSame([
            'result' => false,
            'message' => 'Given payment method is not cancellable.',
        ], $this->validator->isCancellable($payment_method));
    }

    public function testWhenPaymentMethodIsCancellable()
    {
        $payment_method = 'standard';
        $this->assertSame([
            'result' => true,
            'message' => 'Given payment method is cancellable.',
        ], $this->validator->isCancellable($payment_method));
    }
}
