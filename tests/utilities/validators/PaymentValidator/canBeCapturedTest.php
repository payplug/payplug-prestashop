<?php

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
class canBeCapturedTest extends TestCase
{
    protected $oneyValidator;
    private $paymentValidator;
    private $deferred;
    private $installment;

    protected function setUp()
    {
        $this->paymentValidator = new paymentValidator();
        $this->deferred = PaymentMock::getDeferred();
        $this->installment = PaymentMock::getInstallment();
    }

    /**
     * @Description invalid payment data provider
     *
     * @return Generator
     */
    public function invalidPaymentDataProvider()
    {
        yield [false];
        yield ['a string'];
        yield [1000];
        yield [null];
        yield [[]];
    }

    /**
     * @Description invalid is_oney data provider
     *
     * @return Generator
     */
    public function invalidIsOneyDataProvider()
    {
        yield [1001];
        yield ['a string'];
        yield [['key' => 'value']];
        yield [null];
    }

    /**
     * @description  test canBeCapturedWith invalid $payment
     * @dataProvider invalidPaymentDataProvider
     *
     * @param mixed $payment
     */
    public function testWithInvalidPaymentData($payment)
    {
        $is_oney = true;
        $this->assertSame(
            [
            'result' => false,
            'message' => 'Invalid argument, $payment must be a non empty object.',
                          ],
            $this->paymentValidator->canBeCaptured($payment, $is_oney)
        );
    }

    /**
     * @description  test canBeCaptured with invalid $is_oney
     * @dataProvider invalidIsOneyDataProvider
     *
     * @param mixed $is_oney
     */
    public function testWithInvalidIsOneyData($is_oney)
    {
        $payment = $this->deferred;
        $this->assertSame(
            [
                              'result' => false,
                              'message' => 'Invalid argument, $is_oney must be a boolean type.',
                          ],
            $this->paymentValidator->canBeCaptured($payment, $is_oney)
        );
    }

    /**
     * @description test deferred payment capture
     */
    public function testWhenAuthorizationIsNotNull()
    {
        $this->assertTrue(
            $this->paymentValidator->canBeCaptured($this->deferred, false)['result']
        );
    }

    /**
     * @description test standard, oney and installment payment capture
     */
    public function testWhenAuthorizationIsNull()
    {
        $this->assertFalse(
            $this->paymentValidator->canBeCaptured($this->installment, false)['result']
        );
    }
}
