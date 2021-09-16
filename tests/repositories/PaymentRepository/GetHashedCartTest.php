<?php

/**
 * 2013 - 2021 PayPlug SAS.
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
 * @group payment_local
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class GetHashedCartTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $cart = CartMock::get();
        $startTestDate = date('Y-m-d H:i:s');
        $cart->date_add = $cart->date_upd = $startTestDate;
        $cart->id_address_delivery = (string) $cart->id_address_delivery;
        $cart->id_address_invoice = (string) $cart->id_address_invoice;
        $this->paymentDetails = [
            'cartId' => $cart->id,
            'cart' => $cart,
            'paymentMethod' => 'payment_method',
        ];
    }

    public function invalidDataProvider()
    {
        yield [null, 'paymentDetails: null'];
        yield ['I am a string!', 'paymentDetails: ["I am a string!"]'];
        yield [['wrong_parameters'], 'paymentDetails: can\'t find cartId'];
        yield [['cart' => null], 'paymentDetails: {"cart":null}'];
        yield [['cart' => false], 'paymentDetails: {"cart":null}'];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $parameter
     * @param $logMessage
     */
    public function testMethodWithInvalidData($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->getHashedCart($parameter),
            $logMessage
        );
    }

    public function testMethodWithProducts()
    {
        $this->paymentDetails['cart'] = \Mockery::mock('cart');
        $this->paymentDetails['cart']
            ->shouldReceive([
                'getProducts' => CartMock::getProducts(),
            ])
        ;
        $this->repo->getHashedCart($this->paymentDetails);
    }

    public function testMethodWithUpdatedCart()
    {
        $date = new \DateTime();
        $this->paymentDetails['cart'] = \Mockery::mock('cart');
        $this->paymentDetails['cart']
            ->shouldReceive([
                'getProducts' => CartMock::getProducts(),
            ])
        ;
        $this->paymentDetails['cart']->date_upd = $date->format('Y-m-d H:i:s');
        $firstHash = $this->repo->getHashedCart($this->paymentDetails);
        $date->add(new \DateInterval('PT5S'));
        $this->paymentDetails['cart']->date_upd = $date->format('Y-m-d H:i:s');

        $secondHash = $this->repo->getHashedCart($this->paymentDetails);
        $this->assertNotSame($firstHash, $secondHash);
    }

    public function testMethodWithInvalidCartMethod()
    {
        $logMessage = '[getHashedCart] no product found';

        $this->paymentDetails['cart'] = \Mockery::mock('cart');
        $this->paymentDetails['cart']
            ->shouldReceive([
                'getProducts' => false,
            ])
        ;

        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->getHashedCart($this->paymentDetails),
            $logMessage
        );
    }
}
