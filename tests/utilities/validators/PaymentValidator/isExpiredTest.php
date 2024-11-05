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
class isExpiredTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidPaymentDataProvider()
    {
        yield [false];
        yield ['a string'];
        yield [1000];
        yield [null];
        yield [[]];
    }

    /**
     * @dataProvider invalidPaymentDataProvider
     *
     * @param mixed $payment
     */
    public function testWithInvalidPaymentData($payment)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $payment must be a non null object.',
            ],
            $this->validator->isExpired($payment)
        );
    }

    public function testWhenPaymentPropAuthorizationIsNull()
    {
        $payment = PaymentMock::getStandard();
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Missing props, $payment does not contain authorization',
            ],
            $this->validator->isExpired($payment)
        );
    }

    public function testWhenAuthorizationPropsExpiresAtIsMissing()
    {
        $parameters = [
            'authorization' => [
                'authorized_amount' => 424242,
                'authorized_at' => 1669248000,
            ],
        ];
        $payment = PaymentMock::getDeferred($parameters);
        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment->authorization->expires_at should be defined',
        ], $this->validator->isExpired($payment));
    }

    public function testWhenThePaymentIsntExpired()
    {
        $parameters = [
            'authorization' => [
                'authorized_amount' => 424242,
                'authorized_at' => strtotime('-5 days'),
                'expires_at' => strtotime('+2 days'),
            ],
        ];
        $payment = PaymentMock::getDeferred($parameters);

        $this->assertSame([
            'result' => false,
            'message' => 'The payment capture not is expired',
        ], $this->validator->isExpired($payment));
    }

    public function testWhenThePaymentIsExpired()
    {
        $parameters = [
            'authorization' => [
                'authorized_amount' => 424242,
                'authorized_at' => strtotime('-9 days'),
                'expires_at' => strtotime('-2 days'),
            ],
        ];
        $payment = PaymentMock::getDeferred($parameters);

        $this->assertSame([
            'result' => true,
            'message' => 'Payment is expired',
        ], $this->validator->isExpired($payment));
    }
}
