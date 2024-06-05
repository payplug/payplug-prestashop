<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group amount_helper
 *
 * @dontrunTestsInSeparateProcesses
 */
class isEligibleByAmountTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
    }

    /**
     * @description invalid isEligibleByAmount params provider
     *
     * @return \Generator
     */
    public function invalidIsEligibleByAmountDataProvider()
    {
        yield [null, 'giropay', '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$amount must be a int type'];
        yield ['test', 'giropay', '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$amount must be a int type'];
        yield [['key' => 'value'], 'giropay', '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$amount must be a int type'];
        yield [false, 'giropay', '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$amount must be a int type'];

        yield [4242, null, '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$payment_method must be a string type'];
        yield [4242, 42, '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$payment_method must be a string type'];
        yield [4242, ['key' => 'value'], '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$payment_method must be a string type'];
        yield [4242, false, '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '$payment_method must be a string type'];

        yield [4242, 'giropay', null, '$payment_methods_amount must be a string type'];
        yield [4242, 'giropay', 42, '$payment_methods_amount must be a string type'];
        yield [4242, 'giropay', ['key' => 'value'], '$payment_methods_amount must be a string type'];
        yield [4242, 'giropay', false, '$payment_methods_amount must be a string type'];

        yield [50, 'giropay', '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '50 is not eligible for giropay'];
        yield [5000000, 'giropay', '{"giropay":{"min":"EUR:100","max":"EUR:1000000"}}', '5000000 is not eligible for giropay'];
    }

    /**
     * @description valid isEligibleByAmount params provider
     *
     * @return \Generator
     */
    public function validIsEligibleByAmountDataProvider()
    {
        yield [4242, 'applepay', '{"default":{"min":"EUR:99","max":"EUR:2000000"},"giropay":{"min":"EUR:100","max":"EUR:1000000"},"oney":{"min":"EUR:10000","max":"EUR:300000"}}', '4242 is eligible for applepay'];
    }

    /**
     * @description  test isEligibleByAmount with invalid data provider
     *
     * @dataProvider invalidIsEligibleByAmountDataProvider
     *
     * @param $amount
     * @param $payment_method
     * @param $payment_methods_amount
     * @param $errorMsg
     */
    public function testIsEligibleByAmountWithInvalidDataProvider($amount, $payment_method, $payment_methods_amount, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->validator->isEligibleByAmount($amount, $payment_method, $payment_methods_amount)
        );
    }

    /**
     * @description  test isEligibleByAmount with invalid data provider
     *
     * @dataProvider validIsEligibleByAmountDataProvider
     *
     * @param $amount
     * @param $payment_method
     * @param $payment_methods_amount
     * @param $message
     */
    public function testIsEligibleByAmountWithValidDataProvider($amount, $payment_method, $payment_methods_amount, $message)
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => $message,
            ],
            $this->validator->isEligibleByAmount($amount, $payment_method, $payment_methods_amount)
        );
    }
}
