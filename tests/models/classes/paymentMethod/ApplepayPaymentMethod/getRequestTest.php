<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 */
class getRequestTest extends BaseApplepayPaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
    }

    public function tearDown(): void
    {
        // Verify the call count expectations (once, never...) of the mocks
        \Mockery::close();
        parent::tearDown();
    }

    public function testWhenWorkflowIsntFromCheckout()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'address')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'carrier')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'workflow')
            ->andReturn('shopping-cart');
        $this->cart_adapter->shouldReceive([
            'getOrderTotal' => 42,
            'checkQuantities' => true,
        ]);
        $this->class->shouldReceive([
            'getDeliveryOptions' => [
                [
                    'identifier' => '42',
                    'label' => 'carrier label',
                    'detail' => 'carrier detail',
                    'amount' => '42',
                ],
            ],
            'getLinesItems' => [
                [
                    'label' => 'line item',
                    'type' => 'final',
                    'amount' => 42,
                ],
            ],
        ]);

        $expected = [
            'country_code' => 'FR',
            'currency_code' => 'EUR',
            'total' => [
                'label' => 'my mock',
                'amount' => 42,
            ],
            'apple_pay_domain' => 'my-mock.com',
            'carriers' => [
                [
                    'identifier' => '42',
                    'label' => 'carrier label',
                    'detail' => 'carrier detail',
                    'amount' => '42',
                ],
            ],
            'line_items' => [
                [
                    'label' => 'line item',
                    'type' => 'final',
                    'amount' => 42,
                ],
            ],
        ];

        $this->assertSame(
            $expected,
            $this->class->getRequest('cart')
        );
    }

    public function testWhenWorkflowIsFromCheckout()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'carrier')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'workflow')
            ->andReturn('checkout');
        $this->cart_adapter->shouldReceive([
            'getOrderTotal' => 42,
            'checkQuantities' => true,
        ]);
        $this->class->shouldReceive([
            'getDeliveryOptions' => [
                [
                    'identifier' => '42',
                    'label' => 'carrier label',
                    'detail' => 'carrier detail',
                    'amount' => '42',
                ],
            ],
            'getLinesItems' => [
                [
                    'label' => 'line item',
                    'type' => 'final',
                    'amount' => 42,
                ],
            ],
        ]);
        $expected = [
            'country_code' => 'FR',
            'currency_code' => 'EUR',
            'total' => [
                'label' => 'my mock',
                'amount' => 42,
            ],
            'apple_pay_domain' => 'my-mock.com',
        ];
        $this->assertSame($expected, $this->class->getRequest());
    }

    /**
     * @dataProvider invalidUpdateQtyResultProvider
     *
     * @param mixed $update_qty_result
     */
    public function testWhenProductCanNotBeAddedToTheNewCart($update_qty_result)
    {
        $this->mockProductWorkflow();
        $this->cart_adapter->shouldReceive('updateQty')
            ->once()
            ->andReturn($update_qty_result);
        $this->cart_adapter->shouldNotReceive('update');
        $this->cart_adapter->shouldNotReceive('updateAddressId');
        $this->cart_adapter->shouldNotReceive('checkQuantities');
        $this->cart_adapter->shouldNotReceive('getOrderTotal');

        $this->assertSame([
            'result' => false,
            'message' => 'The product can not be added to the cart',
        ], $this->class->getRequest('product'));
        $this->logger->shouldHaveReceived('addLog')
            ->with('ApplepayPaymentMethod::getRequest() - The product can not be added to the cart (cart id: 2)', 'error')
            ->once();

        // The previous cart has been restored
        $this->assertSame(1, $this->context->cart->id);
        $this->assertSame(1, $this->context->cookie->id_cart);
        $this->assertNull($this->context->cookie->previous_cart_id);
    }

    public function invalidUpdateQtyResultProvider()
    {
        yield 'updateQty failed' => [false];

        yield 'minimal quantity not reached' => [-1];

        yield 'invalid arguments given to the adapter' => [[]];
    }

    public function testWhenNewCartProductsCanNotBeOrderedRegardingStock()
    {
        $this->mockProductWorkflow();
        $this->cart_adapter->shouldReceive([
            'updateQty' => true,
            'update' => true,
            'updateAddressId' => true,
        ]);
        // The stock check must be done on the reloaded cart (id 1 from CartMock), not on the stale new cart (id 2)
        $this->cart_adapter->shouldReceive('checkQuantities')
            ->once()
            ->with(\Mockery::on(function ($cart) {
                return 1 === $cart->id;
            }))
            ->andReturn(false);
        $this->cart_adapter->shouldNotReceive('getOrderTotal');
        $this->class->shouldNotReceive('getDeliveryOptions');

        $this->assertSame([
            'result' => false,
            'message' => 'The cart products can not be ordered regarding their stock',
        ], $this->class->getRequest('product'));
        $this->logger->shouldHaveReceived('addLog')
            ->with('ApplepayPaymentMethod::getRequest() - The cart products can not be ordered regarding their stock (cart id: 1)', 'error')
            ->once();

        // The previous cart has been restored
        $this->assertSame(1, $this->context->cookie->id_cart);
        $this->assertNull($this->context->cookie->previous_cart_id);
    }

    public function testWhenCartProductsCanNotBeOrderedRegardingStock()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->cart_adapter->shouldReceive('checkQuantities')
            ->once()
            ->andReturn(false);
        $this->cart_adapter->shouldNotReceive('getOrderTotal');
        $this->cart_adapter->shouldNotReceive('createNewCart');
        $this->class->shouldNotReceive('getDeliveryOptions');
        $this->class->shouldNotReceive('getLinesItems');
        $this->cart_rule_adapter->shouldNotReceive('autoAddToCart');
        $this->context->cookie->shouldNotReceive('write');

        $this->assertSame([
            'result' => false,
            'message' => 'The cart products can not be ordered regarding their stock',
        ], $this->class->getRequest('shopping-cart'));
        $this->logger->shouldHaveReceived('addLog')
            ->with('ApplepayPaymentMethod::getRequest() - The cart products can not be ordered regarding their stock (cart id: 1)', 'error')
            ->once();
    }

    public function testWhenProductIsAddedToTheNewCart()
    {
        $this->mockProductWorkflow();
        $this->cart_adapter->shouldReceive('updateQty')
            ->once()
            ->with(2, 3, 42, 0, 0)
            ->andReturn(true);
        $this->cart_adapter->shouldReceive([
            'update' => true,
            'updateAddressId' => true,
            'getOrderTotal' => 42,
        ]);
        $this->cart_adapter->shouldReceive('checkQuantities')
            ->once()
            ->andReturn(true);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'address')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'carrier')
            ->andReturn(false);
        $this->class->shouldReceive([
            'getDeliveryOptions' => [],
            'getLinesItems' => [],
        ]);

        $this->assertSame([
            'country_code' => 'FR',
            'currency_code' => 'EUR',
            'total' => [
                'label' => 'my mock',
                'amount' => 42,
            ],
            'apple_pay_domain' => 'my-mock.com',
            'line_items' => [],
        ], $this->class->getRequest('product'));

        // The previous cart is kept in the cookie to be restored on cancel
        $this->assertSame(1, $this->context->cookie->previous_cart_id);
    }

    /**
     * @description Mock an Apple Pay request from the product page of product 42:
     * a new cart (id 2) is created for the product, the previous cart (id 1) is saved in the cookie
     */
    private function mockProductWorkflow()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $new_cart = CartMock::get();
        $new_cart->id = 2;
        $this->address_adapter->shouldReceive([
            'getFirstCustomerAddressId' => 42,
        ]);
        $this->cart_adapter->shouldReceive([
            'createNewCart' => $new_cart,
        ]);
        $this->cart_rule_adapter->shouldReceive([
            'autoAddToCart' => true,
        ]);
        $this->context->cookie->shouldReceive([
            'write' => true,
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'empty_cart')
            ->andReturn('1');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'id_product')
            ->andReturn('42');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'group')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'qty')
            ->andReturn('3');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'id_customization')
            ->andReturn(false);
    }
}
