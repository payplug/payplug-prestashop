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

final class IsValidApiPaymentTest extends BasePaymentRepository
{
    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function invalidDataProvider()
    {
        yield [null, 'paymentDetails: null'];
        yield [[(string)'I am a string!'], 'paymentDetails: ["I am a string!"]'];
        yield [['cartId' => null], 'paymentDetails: {"cartId":null}'];
        yield [['cartId' => 'string'], 'paymentDetails: {"cartId":null}'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider invalidDataProvider
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithInvalidData($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage
            ]);

        $this->assertSame(
            $this->repo->isValidApiPayment($parameter),
            $logMessage
        );
    }

    public function testMethodWithValidData()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'payment_method' => 'standard',
                    'id_payment' => 1,
                ],
            ]);
        $paymentDetails = [
            'cartId' => 1,
        ];

        $this->config
            ->shouldReceive([
                                'get' => true
                            ]);


        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'code' => 200,
                    'result' => true,
                    'resource' => PaymentMock::getStandard()
                ]
            ]);


        $this->assertSame(
            $this->repo->isValidApiPayment($paymentDetails),
            [
                'result' => true,
                'paymentDetails' => $paymentDetails,
                'response' => 'Valid API payment/installment'
            ]
        );
    }

    public function testMethodWithThrowException()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'payment_method' => 'standard',
                    'id_payment' => 1
                ]
            ]);

        $this->paymentApi
            ->shouldReceive(['retrieve' => mt_rand()])
            ->andThrow('Payplug\Exception\HttpException', 'Bad request', 400);
    }
}
