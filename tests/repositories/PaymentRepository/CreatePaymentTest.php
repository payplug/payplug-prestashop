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

namespace PayPlugModule\tests\repositories\PaymentRepository;

use PayPlugModule\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CreatePaymentTest extends BasePaymentRepository
{
    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function paymentDetailsParameters()
    {
        yield [null, 'paymentDetails: null'];
        yield [['paymentTab' => null], 'paymentDetails: {"paymentTab":null}'];
        yield [
            ['paymentTab' => ['field' => 'value'], 'paymentMethod' => null],
            'paymentDetails: {"paymentTab":{"field":"value"},"paymentMethod":null}'
        ];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider paymentDetailsParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage
            ]);

        $this->assertSame(
            $this->repo->createPayment($parameter),
            $logMessage
        );
    }

    public function testCreateWithInvalidConfig()
    {
        $paymentDetails = [
            'paymentTab' => mt_rand(),
            'paymentMethod' => 'standard'
        ];

        $this->config
            ->shouldReceive([
                'get' => false
            ]);

        $this->assertSame(
            $this->repo->createPayment($paymentDetails)['response'],
            '[createPayment] Try to create standard  payment with PAYPLUG_STANDARD disabled'
        );
    }

    /**
     * Test creation payment 'standard'
     */
    public function testCreatePaymentWithValidData()
    {
        $paymentDetails = [
            'paymentTab' => mt_rand(),
            'paymentMethod' => 'standard'
        ];

        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 200,
                'result' => true,
                'resource' => PaymentMock::getStandard()
            ]
        ]);

        $this->assertTrue($this->repo->createPayment($paymentDetails)['result']);
        $this->assertSame(
            $this->repo->createPayment($paymentDetails)['response'],
            '[createPayment] Payment successfully created'
        );
    }

    public function testCreatePaymentWithInvalidData()
    {
        $paymentTab = mt_rand();
        $paymentDetails = [
            'paymentTab' => $paymentTab,
            'paymentMethod' => 'standard'
        ];

        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $apiPayment must be an object'
            ]
        ]);

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($paymentDetails),
                'response' => '[createPayment] Exception. Unable to create payment. Error: Invalid argument, $apiPayment must be an object'
            ],
            $this->repo->createPayment($paymentDetails)
        );
    }

    public function testCreatePaymentThrowException()
    {
        $paymentDetails = [
            'paymentTab' => mt_rand(),
            'paymentMethod' => 'standard'
        ];

        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 400,
                'result' => false,
                'message' => 'Bad request'
            ]
        ]);

        $this->assertSame(
            $this->repo->createPayment($paymentDetails)['response'],
            '[createPayment] Exception. Unable to create payment. Error: Bad request'
        );
    }

    public function testCreatePaymentWithEmptyReturnUrl()
    {
        $paymentDetails = [
            'paymentTab' => mt_rand(),
            'paymentMethod' => 'standard',
            'paymentReturnUrl' => null
        ];

        $paymentMock = PaymentMock::getStandard();
        $paymentMock->hosted_payment->return_url = null;

        $this->config
            ->shouldReceive([
                'get' => true
            ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 200,
                'result' => true,
                'resource' => $paymentMock
            ]
        ]);

        $this->assertFalse($this->repo->createPayment($paymentDetails)['result']);
        $this->assertSame(
            $this->repo->createPayment($paymentDetails)['response'],
            '[createPayment] payment return URL is null.'
        );
    }

    public function testCreateIntegratedPaymentReturnUrl()
    {
        $paymentDetails = [
            'paymentMethod' => 'standard',
            'paymentTab'=> [
                'hosted_payment' =>
                    ['return_url' => mt_rand(),
                    ],
            ],
            'paymentReturnUrl' => null,
        ];
        $paymentDetails['paymentTab']['integration'] = 'INTEGRATED_PAYMENT';
        $paymentMock = PaymentMock::getStandard();
        $paymentMock->hosted_payment->return_url = null;
        $this->config
            ->shouldReceive([
                                'get' => true
                            ]);

        $this->dependencies->apiClass->shouldReceive([
            'createPayment' => [
                'code' => 200,
                'result' => true,
                'resource' => $paymentMock
            ]
        ]);

        $this->assertTrue($this->repo->createPayment($paymentDetails)['result']);
        $this->assertSame(
            '[createPayment] Payment successfully created',
            $this->repo->createPayment($paymentDetails)['response']
        );
    }
}
