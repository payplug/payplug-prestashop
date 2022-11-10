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
 *
 * @internal
 * @coversNothing
 */
final class CheckTimeoutPaymentTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $cart = CartMock::get();
        $cart->date_add = $cart->date_upd = null;
        $cart->id_address_delivery = (string) $cart->id_address_delivery;
        $cart->id_address_invoice = (string) $cart->id_address_invoice;

        $this->paymentDetails = [
            'cartId' => $cart->id,
            'cart' => $cart,
            'paymentMethod' => 'payment_method',
        ];
    }

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function checkTimeoutPaymentParameters()
    {
        // Test if (!$idCart)
        yield [null, 'id cart: null'];

        // Test if (!is_int($idCart))
        yield [
            (string) 'I am a string!',
            'id cart: "I am a string!"',
        ];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkTimeoutPaymentParameters
     *
     * @param array  $parameter
     * @param string $logMessage
     *
     * @throws \Exception
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->checkTimeoutPayment($parameter),
            $logMessage
        );
    }

    public function testWithTimeoutLessThan3min()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'date_upd' => (new \DateTime('-2 min'))->format('Y-m-d H:i:s'),
                ],
            ])
        ;

        $this->assertSame(
            true,
            $this->repo->checkTimeoutPayment($this->paymentDetails['cartId'])
        );
    }

    public function testWithTimoutMoreThan3min()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'date_upd' => (new \DateTime('+5 min'))->format('Y-m-d H:i:s'),
                ],
            ])
        ;

        $this->assertSame(
            false,
            $this->repo->checkTimeoutPayment($this->paymentDetails['cartId'])
        );
    }
}
