<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\mock\PaymentMock;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 * @group debug
 *
 * @runTestsInSeparateProcesses
 */
class canBeCapturedTest extends TestCase
{
    private $paymentValidator;
    private $deferred;
    private $installment;

    protected function setUp()
    {
        $this->paymentValidator = new paymentValidator();
        $this->deferred = PaymentMock::getDeferred();
        $this->installment = PaymentMock::getInstallment();
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
                'message' => 'Invalid argument, $payment must be a non empty object.',
            ],
            $this->paymentValidator->canBeCaptured($payment)
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
            $this->paymentValidator->canBeCaptured($payment)
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
        ], $this->paymentValidator->canBeCaptured($payment));
    }

    public function testWhenGivenPaymentHasFailure()
    {
        $parameters = [
            'failure' => [
                'code' => 'timeout',
                'message' => 'failure message',
            ],
        ];
        $payment = PaymentMock::getDeferred($parameters);
        $this->assertSame([
            'result' => false,
            'message' => 'Payment in failure, can not be be captured.',
        ], $this->paymentValidator->canBeCaptured($payment));
    }

    public function testWhenThePaymentIsPaid()
    {
        $parameters = [
            'is_paid' => true,
        ];
        $payment = PaymentMock::getDeferred($parameters);
        $this->assertSame([
            'result' => false,
            'message' => 'The given payment resource is already captured',
        ], $this->paymentValidator->canBeCaptured($payment));
    }

    public function testWhenThePaymentCaptureIsExpired()
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
            'result' => false,
            'message' => 'The payment capture is expired',
        ], $this->paymentValidator->canBeCaptured($payment));
    }

    public function testWhenThePaymentCanBeCaptured()
    {
        $payment = PaymentMock::getDeferred();
        $this->assertSame([
            'result' => true,
            'message' => 'Payment can be captured.',
        ], $this->paymentValidator->canBeCaptured($payment));
    }
}
