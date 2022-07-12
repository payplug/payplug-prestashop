<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 * @group create_payment
 *
 * @runTestsInSeparateProcesses
 */
final class CreatePaymentTest extends BasePaymentRepository
{
    private $paymentDetails;
    private $truncatedPaymentDetails;
    private $payment;
    private $installment;

    public function setUp()
    {
        parent::setUp();

        $this->paymentDetails = [
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => 'standard',
            'cartId' => 42,
        ];

        $this->payment = PaymentMock::getStandard();
        $this->installment = PaymentMock::getInstallment();
    }

    public function paymentDetailsParameters()
    {
        // Invalid paymentTab, not empty array expected
        yield [[
            'paymentTab' => null,
            'paymentMethod' => 'standard',
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => false,
            'paymentMethod' => 'standard',
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => 42,
            'paymentMethod' => 'standard',
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => 'wrong parameter',
            'paymentMethod' => 'standard',
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => [],
            'paymentMethod' => 'standard',
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];

        // Invalid paymentMethod, string expected
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => null,
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => false,
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => 42,
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => ['key' => 'value'],
            'cartId' => 42,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];

        // Invalid cartId, int expected
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => 'standard',
            'cartId' => null,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => 'standard',
            'cartId' => false,
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => 'standard',
            'cartId' => 'wrong parameter',
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
        yield [[
            'paymentTab' => ['key' => 'value'],
            'paymentMethod' => 'standard',
            'cartId' => ['key' => 'value'],
        ], 'paymentDetails: Invalid paymentTab, not empty array expected'];
    }

    /**
     * @dataProvider paymentDetailsParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithWrongParameters($parameters, $returnMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $returnMessage
            ]);

        $this->assertSame(
            $this->repo->createPayment($parameters),
            $returnMessage
        );
    }

    public function testCreateWithInvalidConfig()
    {
        $this->config
            ->shouldReceive([
                'get' => false
            ]);

        $this->assertSame(
            $this->repo->createPayment($this->paymentDetails),
            [
                'result' => false,
                'Configuration::get' => 'false',
                'response' => '[createPayment] Try to create standard payment with PAYPLUG_STANDARD disabled'
            ]
        );
    }

    public function testIfPaymentExistsAndCannotBeAborted()
    {
        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->repo->shouldReceive([
            'checkPaymentTable' => [
                'id_payment' => 'pay_123456789',
                'payment_method' => 'standard',
            ]
        ]);

        $this->dependencies->apiClass->shouldReceive([
            'retrievePayment' => [
                'code' => 200,
                'result' => true,
                'resource' => PaymentMock::getStandard()
            ],
            'abortPayment' => [
                'code' => 500,
                'result' => false,
                'message' => 'Payment cannot be aborted'
            ]
        ]);

        $this->assertSame(
            [
                'result' => false,
                'paymentId' => json_encode('pay_123456789'),
                'response' => '[createPayment] Exception. Unable to abort payment. Error: Payment cannot be aborted'
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }

    public function testPaymentCannotBeCreated()
    {
        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->repo->shouldReceive([
            'checkPaymentTable' => []
        ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 500,
                'result' => false,
                'message' => 'Payment cannot be created'
            ]
        ]);

        $this->truncatedPaymentDetails = array_diff_key($this->paymentDetails, array_flip(['paymentTab']));

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->truncatedPaymentDetails),
                'response' => '[createPayment] Exception. Unable to create payment. Error: Payment cannot be created'
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }

    public function testInstallmentPlanCannotBeCreated()
    {
        $paymentDetails = $this->paymentDetails;
        $paymentDetails['paymentMethod'] = 'installment';

        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->repo->shouldReceive([
            'checkPaymentTable' => []
        ]);

        $this->dependencies->apiClass->shouldReceive([
            'createInstallment' => [
                'code' => 500,
                'result' => false,
                'message' => 'Installment plan cannot be created'
            ]
        ]);

        $this->truncatedPaymentDetails = array_diff_key($paymentDetails, array_flip(['paymentTab']));

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->truncatedPaymentDetails),
                'response' => '[createPayment] Exception. Unable to create installment plan. Error: Installment plan cannot be created'
            ],
            $this->repo->createPayment($paymentDetails)
        );
    }

    // todo: create the payment mock with the failure to achieve this method
    public function atestPaymentCreatedHasFailure()
    {
        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->repo->shouldReceive([
            'checkPaymentTable' => []
        ]);

        $payment = $this->payment;
        // next : create a payment mock with a failure or adapt current payment mock to give extra params

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 200,
                'result' => true,
                'message' => $this->payment
            ]
        ]);

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => '[createPayment] Exception. Unable to create installment plan. Error: Installment plan cannot be created'
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }

    // todo: create testInstallmentPlanCreatedhasFailure method
    // todo: create testNoResourceIDIsGiven method
    // todo: create testNoPaymentReturnUrlIsSetted method

    // todo: create testIfPaymentExistsAndCanBeAborted method
    // todo: create testPaymentIsCreated method
    // todo: create testInstallmentPlanIsCreated method

    public function testCreatePaymentWithEmptyReturnUrl()
    {
        $paymentMock = PaymentMock::getStandard();
        // todo: we should mock the return of the who give back the payment resource and not set it like bellow
        $paymentMock->hosted_payment->return_url = null;

        $paymentDetails = $this->paymentDetails;
        $paymentDetails['paymentId'] = $paymentMock->id;
        $paymentDetails['paymentReturnUrl'] = null;

        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->repo->shouldReceive([
            'checkPaymentTable' => []
        ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 200,
                'result' => true,
                'resource' => $paymentMock
            ]
        ]);

        $this->truncatedPaymentDetails = array_diff_key($paymentDetails, array_flip(['paymentTab']));

        $this->assertFalse($this->repo->createPayment($this->paymentDetails)['result']);
        $this->assertSame(
            $this->repo->createPayment($this->paymentDetails),
            [
                'result' => false,
                'paymentDetails' => json_encode($this->truncatedPaymentDetails),
                'response' => "[createPayment] payment return URL is null.",
            ]
        );
    }

    public function testCreateIntegratedPaymentReturnUrl()
    {
        $paymentMock = PaymentMock::getStandard();

        $paymentDetails = $this->paymentDetails;
        $paymentDetails['paymentId'] = $paymentMock->id;
        $paymentDetails['paymentReturnUrl'] = $paymentMock->hosted_payment->return_url;
        $paymentDetails['isPaid'] = false;
        $paymentDetails['paymentUrl'] = $paymentMock->hosted_payment->payment_url;

        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->repo->shouldReceive([
            'checkPaymentTable' => []
        ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 200,
                'result' => true,
                'resource' => $paymentMock
            ]
        ]);

        $this->assertSame(
            $this->repo->createPayment($this->paymentDetails),
            [
                'result' => true,
                'paymentDetails' => $paymentDetails,
                'resource' => $paymentMock,
                'response' => "[createPayment] Payment successfully created",
            ]
        );
    }
}
