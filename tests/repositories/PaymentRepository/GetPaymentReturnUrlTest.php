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
use PayPlug\tests\repositories\BaseTest;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetPaymentReturnUrlTest extends BaseTest
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
    public function checkGetPaymentReturnUrlParameters()
    {
        // Test if (!$paymentDetails)
        yield [null, 'paymentDetails: null'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkGetPaymentReturnUrlParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $arrayLog = [];
        MockHelper::createAddLogMock($this->logger, $arrayLog);

        $response = $this->paymentRepository->getpaymentReturnUrl($parameter);

        $this->assertFalse(
            $response['result'],
            'ERROR : the response is true'
        );

        $this->assertSame(
            $response['response'],
            '[getPaymentReturnUrl] $paymentDetails is null'
        );

        $this->assertSame(
            $arrayLog['message'],
            $logMessage
        );
    }

//    public function testCreatePaymentWithValidData()
//    {
//
//    }
//
//    public function testCreatePaymentWithInvalidData()
//    {
//
//    }
//
//    public function testCreatePaymentThrowException($parameter)
//    {
//
//    }
}
