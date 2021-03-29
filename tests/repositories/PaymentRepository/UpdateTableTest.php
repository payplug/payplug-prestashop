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

use PayPlug\tests\mock\CartMock;

/**
 * @group dev
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class UpdateTableTest extends BasePaymentRepository
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
        yield [['cart' => null], 'paymentDetails: {"cart":null}'];
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
                'paymentError' => $logMessage
            ]);

        $this->assertSame(
            $this->repo->updateTable($parameter),
            $logMessage
        );
    }

    public function testWithCreatePaymentThrowingException()
    {
        $paymentDetails = [
            'cart' => CartMock::get(),
            'paymentId' => 1,
            'paymentMethod' => 'standard',
            'paymentUrl' => 'htt://www.monsite.com',
            'paymentReturnUrl' => 'htt://www.monsite.com',
            'authorizedAt' => '2021-01-01 00:00:00',
            'isPaid' => true,
            'cartId' => 1
        ];

        $response = $this->repo->updateTable($paymentDetails);
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
