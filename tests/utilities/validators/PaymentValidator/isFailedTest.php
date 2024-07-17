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
class isFailedTest extends TestCase
{
    protected $validator;

    protected function setUp()
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
        ], $this->validator->isFailed($payment));
    }

    public function testWhenPaymentPropFailureIsMissing()
    {
        $payment = new \stdClass();
        $payment->id = 'pay_5ktNvd3BNCp6GPcqIZvY9j';
        $payment->object = 'payment';

        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment does not contain failure props',
        ], $this->validator->isFailed($payment));
    }

    public function testWhenPaymentFailurePropMessageIsMissing()
    {
        $parameters = [
            'failure' => [
                'code' => 'timeout',
            ],
        ];
        $payment = PaymentMock::getStandard($parameters);

        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment failure does not contain message props',
        ], $this->validator->isFailed($payment));
    }

    public function testWhenPaymentFailurePropCodeIsMissing()
    {
        $parameters = [
            'failure' => [
                'message' => 'error message timeout',
            ],
        ];
        $payment = PaymentMock::getStandard($parameters);

        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment failure does not contain code props',
        ], $this->validator->isFailed($payment));
    }

    public function testWhenPaymentHasNoFailure()
    {
        $payment = PaymentMock::getStandard();

        $this->assertSame([
            'result' => false,
            'message' => 'Payment does not contain failure',
        ], $this->validator->isFailed($payment));
    }

    public function testWhenPaymentHasFailure()
    {
        $parameters = [
            'failure' => [
                'code' => 'timeout',
                'message' => 'error message timeout',
            ],
        ];
        $payment = PaymentMock::getStandard($parameters);

        $this->assertSame([
            'result' => true,
            'message' => 'error message timeout',
        ], $this->validator->isFailed($payment));
    }
}
