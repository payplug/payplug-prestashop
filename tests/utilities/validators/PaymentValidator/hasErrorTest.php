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
class hasErrorTest extends TestCase
{
    private $paymentValidator;

    protected function setUp()
    {
        $this->paymentValidator = new paymentValidator();
    }

    /**
     * @description invalid data provider
     *
     * @return Generator
     */
    public function invalidPDataProvider()
    {
        // invalid $payment array
        yield [[], '$payment must be a non empty array.'];
        yield [false, '$payment must be a non empty array.'];
        yield [1, '$payment must be a non empty array.'];
        yield [null, '$payment must be a non empty array.'];

        //invalid $payment['result']
        yield [['result' => ''], 'result argument inside payment array must be a non empty boolean.'];
        yield [['result' => ['key' => 'value']], 'result argument inside payment array must be a non empty boolean.'];
        yield [['result' => null], 'result argument inside payment array must be a non empty boolean.'];
        yield [['result' => 300], 'result argument inside payment array must be a non empty boolean.'];
    }

    /**
     * @dataProvider invalidPDataProvider
     *
     * @param mixed $payment
     * @param mixed $error_message
     */
    public function testHasErrorWithInvalidData($payment, $error_message)
    {
        $this->assertSame(['result' => true, 'message' => $error_message], $this->paymentValidator->hasError($payment));
    }

    /**
     * @description asserting payment in error
     */
    public function testPaymentInError()
    {
        $payment = ['result' => false];
        $this->assertSame(
            ['result' => true, 'message' => '$payment is failed.'],
            $this->paymentValidator->hasError($payment)
        );
    }

    /**
     * @description asserting payment in success
     */
    public function testPaymentInSuccess()
    {
        $payment = ['result' => true];
        $this->assertSame(
            ['result' => false, 'message' => '$payment is succeeded.'],
            $this->paymentValidator->hasError($payment)
        );
    }
}
