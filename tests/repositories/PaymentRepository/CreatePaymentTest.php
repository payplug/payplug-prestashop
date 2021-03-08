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

use PayPlug\src\entities\PaymentEntity;
use PayPlug\src\repositories\PaymentRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\mock\PaymentTabMock;
use PayPlug\tests\repositories\BaseTest;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CreatePaymentTest extends BaseTest
{
    private $paymentRepository;

    public function setUp()
    {
        parent::setUp();
        parent::apiCall();

        $this->logger->shouldReceive([
            'setParams' => $this->logger,
        ]);

        $this->paymentRepository = new PaymentRepository(
            $this->payplug,
            $this->cart,
            $this->logger,
            new PaymentEntity(),
            null
        );

        $this->payplug
            ->shouldReceive('setPaymentErrorsCookie')
            ->andReturn(true);
    }

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function paymentDetailsParameters()
    {
        // Test if (!$paymentDetails)
        yield [null, '[createPayment] $paymentDetails is null'];

        // Test if (!$paymentDetails['paymentTab'])
        yield [
            ['paymentTab' => null],
            '[createPayment] $paymentDetails[\'paymentTab\'] is null'
        ];

        // Test if (!$paymentDetails['paymentMethod'])
        yield [
            [
                'paymentTab' => ['field' => 'value'],
                'paymentMethod' => null
            ],
            '[createPayment] $paymentDetails[\'paymentMethod\'] is null'
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
        $arrayLog = [];
        MockHelper::createAddLogMock($this->logger, $arrayLog);

        $response = $this->paymentRepository->createPayment($parameter);

        // Test 1 : On va checker return $this->displayErrorPayment
        $this->assertFalse(
            $response['result'],
            'ERROR : the response is true'
        );

        // Test 2 : On compare les messages du retour
        $this->assertSame(
            $response['response'],
            'Error during payment creation'
        );

        // Test 3 : On compare le message du logger à écrire et celui écrit
        $this->assertSame(
            $arrayLog['message'],
            $logMessage
        );
    }

    /**
     * Test creation payment 'standard'
     */
    public function testCreatePaymentWithValidData()
    {
        $arrayLog = [];
        MockHelper::createAddLogMock($this->logger, $arrayLog);
        
        $paymentDetails = [
            'paymentTab'    => PaymentTabMock::getStandard(),
            'paymentMethod' => 'standard'
        ];

        $paymentMockStandard = PaymentMock::getStandard();
        
        $this->paymentApi
            ->shouldReceive('create')
            ->andReturn($paymentMockStandard);

        $response = $this->paymentRepository->createPayment($paymentDetails);
    }

    public function testCreatePaymentWithInvalidData()
    {
        $paymentDetails = [
        'paymentTab'    => PaymentTabMock::getStandard(),
        'paymentMethod' => 'standard'
        ];

        $arrayLog = [];
        MockHelper::createAddLogMock($this->logger, $arrayLog);

        $paymentMockStandard = PaymentMock::getStandard();

        $this->paymentApi
            ->shouldReceive('create')
            ->andReturn([
                'result' => false,
                'payment_tab' => $paymentDetails['paymentTab'],
                'response' => [0 => 'Payplug\\Exception\\BadRequestException: [400]: Bad request']
            ]);

        $response = $this->paymentRepository->createPayment($paymentDetails);

        var_dump($arrayLog);
    }

//    public function testCreatePaymentThrowException($parameter)
//    {
//
//    }
}
