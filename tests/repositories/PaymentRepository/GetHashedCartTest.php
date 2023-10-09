<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group old_repository
 * @group old_payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetHashedCartTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $startTestDate = date('Y-m-d H:i:s');
        $cartMock = CartMock::get();
        $cart = \Mockery::mock('cart');
        $cart->id = $cartMock->id;
        $cart->id_address_delivery = $cartMock->id_address_delivery;
        $cart->id_address_invoice = $cartMock->id_address_invoice;
        $cart->id_currency = $cartMock->id_currency;
        $cart->id_customer = $cartMock->id_customer;
        $cart->delivery_option = $cartMock->delivery_option;
        $cart->date_add = $startTestDate;
        $cart->date_upd = $startTestDate;

        $this->paymentDetails = [
            'cartId' => $cart->id,
            'cart' => $cart,
            'method' => 'payment_method',
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
        $this->paymentDetails['cart']
            ->shouldReceive([
                'getProducts' => CartMock::getProducts(),
                'getOrderTotal' => 42.42,
            ])
        ;
        $this->repo->getHashedCart($this->paymentDetails);
    }

    public function invalidCartDataProvider()
    {
        yield ['id_address_delivery', 42];
        yield ['id_address_invoice', 42];
        yield ['id_currency', 42];
        yield ['id_customer', 42];
        yield ['delivery_option', '{42, 42}'];
    }

    /**
     * @dataProvider invalidCartDataProvider
     *
     * @param $type
     * @param $value
     */
    public function testMethodWithDifferentCartData($type, $value)
    {
        $this->paymentDetails['cart']
            ->shouldReceive([
                'getProducts' => CartMock::getProducts(),
                'getOrderTotal' => 42.42,
            ])
        ;

        $firstHash = $this->repo->getHashedCart($this->paymentDetails);

        $this->paymentDetails['cart']->{$type} = $value;
        $secondHash = $this->repo->getHashedCart($this->paymentDetails);
        $this->assertNotSame($firstHash, $secondHash);
    }

    public function testMethodWithAmountCart()
    {
        $this->paymentDetails['cart']
            ->shouldReceive([
                'getProducts' => CartMock::getProducts(),
            ])
        ;
        $this->paymentDetails['cart']
            ->shouldReceive('getOrderTotal')
            ->once()
            ->andReturn(42.42)
        ;

        $firstHash = $this->repo->getHashedCart($this->paymentDetails);

        $this->paymentDetails['cart']
            ->shouldReceive('getOrderTotal')
            ->once()
            ->andReturn(142.42)
        ;

        $secondHash = $this->repo->getHashedCart($this->paymentDetails);
        $this->assertNotSame($firstHash, $secondHash);
    }

    public function testMethodWithInvalidCartMethod()
    {
        $logMessage = '[getHashedCart] no product found';

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
