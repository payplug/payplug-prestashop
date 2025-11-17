<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 */
class isEligibleByAmountTest extends TestCase
{
    use FormatDataProvider;

    protected $validator;
    protected $amount;
    protected $payment_method;
    protected $payment_methods_amount;

    public function setUp()
    {
        $this->validator = new paymentValidator();
        $this->amount = 4200;
        $this->payment_method = 'default';
        $this->payment_methods_amount = '{"default":{"min":"EUR:100","max":"EUR:1000000"}}';
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param $amount
     */
    public function testWhenGivenAmountIsInvalidIntegerFormat($amount)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => '$amount must be a int type',
            ],
            $this->validator->isEligibleByAmount($amount, $this->payment_method, $this->payment_methods_amount)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param $payment_method
     */
    public function testWhenGivenPaymentMethodIsInvalidStringFormat($payment_method)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => '$payment_method must be a string type',
            ],
            $this->validator->isEligibleByAmount($this->amount, $payment_method, $this->payment_methods_amount)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param $payment_methods_amount
     */
    public function testWhenGivenPaymentMethodAmountIsInvalidStringFormat($payment_methods_amount)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => '$payment_methods_amount must be a string type',
            ],
            $this->validator->isEligibleByAmount($this->amount, $this->payment_method, $payment_methods_amount)
        );
    }

    public function testWhenGivenAmountIsToLow()
    {
        $amount = 50;
        $this->assertSame(
            [
                'result' => false,
                'message' => $amount . ' is not eligible for ' . $this->payment_method,
            ],
            $this->validator->isEligibleByAmount($amount, $this->payment_method, $this->payment_methods_amount)
        );
    }

    public function testWhenGivenAmountIsToHight()
    {
        $amount = 5000000;
        $this->assertSame(
            [
                'result' => false,
                'message' => $amount . ' is not eligible for ' . $this->payment_method,
            ],
            $this->validator->isEligibleByAmount($amount, $this->payment_method, $this->payment_methods_amount)
        );
    }

    public function testWhenGivenAmountIsElligible()
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => $this->amount . ' is eligible for ' . $this->payment_method,
            ],
            $this->validator->isEligibleByAmount($this->amount, $this->payment_method, $this->payment_methods_amount)
        );
    }
}
