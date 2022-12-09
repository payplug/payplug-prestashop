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
class isRefundableAmountTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [0];
        yield [['key' => 'value']];
        yield [true];
        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsInvalidIntegerFormat($amount)
    {
        $limit = 1000;
        $this->assertSame([
            'result' => false,
            'code' => 'format',
            'message' => 'Invalid argument given, $amount must be a non null integer',
        ], $this->validator->isRefundableAmount($amount, $limit));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $limit
     */
    public function testWhenGivenLimitIsInvalidIntegerFormat($limit)
    {
        $amount = 42;
        $this->assertSame([
            'result' => false,
            'code' => 'format',
            'message' => 'Invalid argument given, $limit must be a non null integer',
        ], $this->validator->isRefundableAmount($amount, $limit));
    }

    public function testWhenGivenAmountIsToLow()
    {
        $amount = 9;
        $limit = 1000;
        $this->assertSame([
            'result' => false,
            'code' => 'lower',
            'message' => 'The given amount is to low',
        ], $this->validator->isRefundableAmount($amount, $limit));
    }

    public function testWhenGivenAmountIsBeyondTheLimit()
    {
        $amount = 1000;
        $limit = 42;
        $this->assertSame([
            'result' => false,
            'code' => 'upper',
            'message' => 'The given amount exceed the given limit',
        ], $this->validator->isRefundableAmount($amount, $limit));
    }

    public function testWhenGivenAmountIsValid()
    {
        $amount = 42;
        $limit = 1000;
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isRefundableAmount($amount, $limit));
    }
}
