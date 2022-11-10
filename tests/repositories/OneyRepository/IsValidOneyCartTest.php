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

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class IsValidOneyCartTest extends BaseOneyRepository
{
    public function invalidCartDataProvider()
    {
        yield ['wrong cart'];
        yield [['cart array']];
        yield [null];
    }

    /**
     * @dataProvider invalidCartDataProvider
     *
     * @param mixed $cart
     */
    public function testWithInvalidCart($cart)
    {
        $this->assertSame(
            [
                'result' => false,
                'error' => 'The cart is unvalid',
            ],
            $this->repo->isValidOneyCart($cart)
        );
    }

    public function testWithTooMuchProducts()
    {
        $this->cart
            ->shouldReceive([
                'nbProducts' => 1001,
            ])
        ;

        $cart = CartMock::get();

        $this->assertSame(
            [
                'result' => false,
                'error' => 'The payment with Oney is not available because you have more than 1000 items in your cart.',
            ],
            $this->repo->isValidOneyCart($cart)
        );
    }

    public function testWithValudProductsNB()
    {
        $this->cart
            ->shouldReceive([
                'nbProducts' => 999,
            ])
        ;

        $cart = CartMock::get();

        $this->assertSame(
            [
                'result' => true,
                'error' => false,
            ],
            $this->repo->isValidOneyCart($cart)
        );
    }
}
