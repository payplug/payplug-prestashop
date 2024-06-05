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
class isTimeoutCachedPaymentTest extends TestCase
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

    public function invalidDateTimeFormatDataProvider()
    {
        yield ['1970-01-01'];
        yield ['00:00:00 1970-01-01'];
        yield ['01-01-1970 00:00:00'];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date
     */
    public function testWithInvalidDateFormat($date)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $date must be a non empty string',
        ], $this->validator->isTimeoutCachedPayment($date));
    }

    /**
     * @dataProvider invalidDateTimeFormatDataProvider
     *
     * @param mixed $date
     */
    public function testWithInvalidDateTimeFormat($date)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $date must be a date in format Y-m-d H:i:s',
        ], $this->validator->isTimeoutCachedPayment($date));
    }

    public function testWhenGivenDateIsTimeout()
    {
        $date = date('Y-m-d H:i:s', strtotime('-12 minutes'));
        $this->assertSame([
            'result' => false,
            'message' => 'Given date is timeout',
        ], $this->validator->isTimeoutCachedPayment($date));
    }

    public function testWhenGivenDateIsValid()
    {
        $date = date('Y-m-d H:i:s', strtotime('-2 minutes'));
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isTimeoutCachedPayment($date));
    }
}
