<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
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
