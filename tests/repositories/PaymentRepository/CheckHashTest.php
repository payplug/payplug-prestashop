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
final class CheckHashTest extends BaseTest
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
    public function checkHashParameters()
    {
        // Test if (!$paymentDetails)
        yield [null, 'paymentDetails: null'];

        // Test if (!is_array($paymentDetails))
        yield [
            [(string)'I am a string!'],
            'paymentDetails: ["I am a string!"]'
        ];

        // Test if (!$paymentDetails['cartId'])
        yield [
            [
                'cartId' => null
            ],
            'paymentDetails: {"cartId":null}'
        ];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkHashParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $arrayLog = [];
        MockHelper::createAddLogMock($this->logger, $arrayLog);

        $response = $this->paymentRepository->checkHash($parameter);

        $this->assertFalse(
            $response['result'],
            'ERROR : the response is true'
        );

        $this->assertSame(
            $response['response'],
            '[checkHash] $paymentDetails or cartId is null, or $paymentDetails is not an array'
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
