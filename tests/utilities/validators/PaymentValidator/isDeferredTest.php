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
class isDeferredTest extends TestCase
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
            'message' => 'Invalid argument given, $payment must be a non empty object',
        ], $this->validator->isDeferred($payment));
    }

    public function testWhenPaymentGivenIsOneyResource()
    {
        $payment = PaymentMock::getOney();
        $this->validator->isDeferred($payment);
        $this->assertSame([
            'result' => false,
            'message' => 'Given $payment is created with oney, it cannot be deferred',
        ], $this->validator->isDeferred($payment));
    }

    public function testWhenPaymentPropsAuthorizationIsMissing()
    {
        $payment = PaymentMock::getStandard();
        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment does not contain authorization',
        ], $this->validator->isDeferred($payment));
    }

    public function testWhenAuthorizationPropsAuthorizedAtIsMissing()
    {
        $parameters = [
            'authorization' => [
                'authorized_amount' => 424242,
                'expires_at' => 1669248000,
            ],
        ];
        $payment = PaymentMock::getDeferred($parameters);
        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment->authorization->authorized_at should be defined',
        ], $this->validator->isDeferred($payment));
    }

    public function testWhenAuthorizationPropsAuthorizedAtIsNull()
    {
        $parameters = [
            'authorization' => [
                'authorized_at' => null,
                'authorized_amount' => 424242,
                'expires_at' => 1669248000,
            ],
        ];
        $payment = PaymentMock::getDeferred($parameters);
        $this->assertSame([
            'result' => false,
            'message' => 'Missing props, $payment->authorization->authorized_at should be defined',
        ], $this->validator->isDeferred($payment));
    }

    public function testWhenGivenPaymentIsDeferred()
    {
        $payment = PaymentMock::getDeferred();
        $this->assertSame([
            'result' => true,
            'message' => 'Current ressource is a deferred payment',
        ], $this->validator->isDeferred($payment));
    }
}
