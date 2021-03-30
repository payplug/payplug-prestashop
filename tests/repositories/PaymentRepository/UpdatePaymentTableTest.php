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
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class UpdatePaymentTableTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $cart = CartMock::get();
        $cart->date_add = $cart->date_upd = null;
        $cart->id_address_delivery = (string)$cart->id_address_delivery;
        $cart->id_address_invoice = (string)$cart->id_address_invoice;

        $this->paymentDetails = [
            'cartId' => $cart->id,
            'cart' => $cart,
            'paymentMethod' => 'payment_method',
            'paymentUrl' => 'payment_url',
            'authorizedAt' => 'authorized_at',
            'isPaid' => 'is_paid',
        ];
    }


    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function checkUpdatePaymentTableParameters()
    {
        /*
         * if (!$paymentDetails || !is_array($paymentDetails) || !$paymentDetails['cart']) {
         */
        // Test if (!$paymentDetails)
        yield [null, 'paymentDetails: null'];

        // Test if (!is_array($paymentDetails))
        yield [[(string)'I am a string!'], 'paymentDetails: ["I am a string!"]'];

        // Test if (!$paymentDetails['cart'])
        yield [['cart' => null], 'paymentDetails: {"cart":null}'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkUpdatePaymentTableParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $response = $this->repo->updatePaymentTable($parameter);

        $this->assertFalse(
            $response['result'],
            'ERROR : the response is true'
        );

        $this->assertSame(
            $response['response'],
            '[updatePaymentTable] $paymentDetails or cart is null, or $paymentDetails is not an array'
        );

        $this->assertSame(
            $this->arrayLogger['message'],
            $logMessage
        );
    }
}
