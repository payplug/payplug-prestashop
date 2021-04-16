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
use PayPlug\tests\mock\PaymentTabMock;

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
        // Test if (!$paymentDetails)
        yield [null, 'paymentDetails: null'];

        // Test if (!$paymentDetails['paymentTab'])
        yield [
            ['paymentTab' => null],
            'paymentDetails: {"paymentTab":null}'
        ];

        // Test if (!$paymentDetails['paymentMethod'])
        yield [
            [
                'paymentTab' => ['field' => 'value'],
                'paymentMethod' => null
            ],
            'paymentDetails: {"paymentTab":{"field":"value"},"paymentMethod":null}'
        ];
    }

    /**
     * Test methos with nulled $paiementDetails
     *
     * @dataProvider paymentDetailsParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $response = $this->repo->createPayment($parameter);

        // Test 1 : On va checker return $this->displayErrorPayment
        $this->assertFalse(
            $response['result'],
            'ERROR : the response is true'
        );

        // Test 2 : On compare les messages du retour
        $this->assertSame(
            $response['response'],
            '[createPayment] $paymentDetails or paymentTab or paymentMethod is null'
        );

        // Test 3 : On compare le message du logger à écrire et celui écrit
        $this->assertSame(
            $this->arrayLogger['message'],
            $logMessage
        );
    }

    /**
     * Test creation payment 'standard'
     */
    public function testCreatePaymentWithValidData()
    {
        $paymentDetails = [
            'paymentTab' => PaymentTabMock::getStandard(),
            'paymentMethod' => 'standard'
        ];

        $paymentMockStandard = PaymentMock::getStandard();

        $this->paymentApi
            ->shouldReceive('create')
            ->andReturn([
                'result' => true,
                'paimentDetails' => $paymentDetails,
                'response' => '[createPayment] Payment successfully created'
            ]);

        $response = $this->repo->createPayment($paymentDetails);
    }

    public function testCreatePaymentWithInvalidData()
    {
        $paymentDetails = [
            'paymentTab' => PaymentTabMock::getStandard(),
            'paymentMethod' => 'standard'
        ];

        $paymentMockStandard = PaymentMock::getStandard();

        $this->paymentApi
            ->shouldReceive('create')
            ->andReturn([
                'result' => false,
                'paymentDetails' => $paymentDetails,
                'response' => '[createPayment] Exception. Unable to create payment. Error: Bad request'
            ]);

        $response = $this->repo->createPayment($paymentDetails);
    }

//    public function testCreatePaymentThrowException($parameter)
//    {
//
//    }
}
