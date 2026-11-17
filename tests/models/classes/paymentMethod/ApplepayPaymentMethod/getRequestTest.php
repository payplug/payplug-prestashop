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
    private $cart;

    public function setUp(): void
    {
        parent::setUp();
        $this->cart = CartMock::get();
        $this->cart_adapter->shouldReceive([
            'get' => $this->cart,
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
        $this->mockCheckoutCustomer(true);
        $this->cart_adapter->shouldReceive([
            'isVirtualCart' => false,
        ]);
        $this->cart_adapter->shouldNotReceive('getDeliveryOptionList');
        $this->cart_adapter->shouldNotReceive('setDeliveryOption');
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
     * @dataProvider incompleteCheckoutCartProvider
     *
     * @param bool $is_customer_loaded
     * @param array $cart_values
     * @param string $missing_data
     */
    public function testWhenCheckoutCartIsIncomplete($is_customer_loaded, $cart_values, $missing_data)
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer($is_customer_loaded);
        foreach ($cart_values as $key => $value) {
            $this->cart->{$key} = $value;
        }
        $this->cart_adapter->shouldReceive([
            'isVirtualCart' => false,
            'checkQuantities' => true,
        ]);
        $this->cart_adapter->shouldNotReceive('getOrderTotal');

        $this->assertSame([
            'result' => false,
            'message' => 'The checkout cart has no ' . $missing_data,
        ], $this->class->getRequest('checkout'));
        $this->logger->shouldHaveReceived('addLog')
            ->with('ApplepayPaymentMethod::getRequest() - The checkout cart has no ' . $missing_data . ' (cart id: 1)', 'error')
            ->once();
    }

    public function incompleteCheckoutCartProvider()
    {
        yield 'customer not loaded' => [false, [], 'customer'];

        yield 'cart without customer' => [true, ['id_customer' => 0], 'customer'];

        yield 'cart without delivery address' => [true, ['id_address_delivery' => 0], 'delivery address'];

        yield 'cart without invoice address' => [true, ['id_address_invoice' => 0], 'invoice address'];

        yield 'cart without carrier' => [true, ['id_carrier' => 0], 'carrier'];
    }

    public function testWhenPostedDeliveryOptionIsAvailableItIsSavedOnTheCheckoutCart()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer(true, '2,');
        $this->cart->id_carrier = 0;
        $this->cart->delivery_option = '';
        $this->cart_adapter->shouldReceive('getDeliveryOptionList')
            ->once()
            ->with(1)
            ->andReturn([
                2 => [
                    '1,' => [],
                    '2,' => [],
                ],
            ]);
        $this->cart_adapter->shouldReceive('setDeliveryOption')
            ->once()
            ->with($this->cart, [2 => '2,'])
            ->andReturnUsing(function ($cart) {
                $cart->id_carrier = 2;

                return true;
            });
        $this->cart_adapter->shouldReceive('getOrderTotal')
            ->once()
            ->with(1, true, 2)
            ->andReturn(42);
        $this->cart_adapter->shouldReceive([
            'checkQuantities' => true,
        ]);

        $this->assertSame([
            'country_code' => 'FR',
            'currency_code' => 'EUR',
            'total' => [
                'label' => 'my mock',
                'amount' => 42,
            ],
            'apple_pay_domain' => 'my-mock.com',
        ], $this->class->getRequest('checkout'));
    }

    public function testWhenPostedDeliveryOptionIsAlreadyTheCartOneItIsNotSavedAgain()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer(true, '1,');
        $this->cart->delivery_option = '{"2":"1,"}';
        $this->cart_adapter->shouldReceive('getDeliveryOptionList')
            ->andReturn([
                2 => [
                    '1,' => [],
                ],
            ]);
        $this->cart_adapter->shouldNotReceive('setDeliveryOption');
        $this->cart_adapter->shouldReceive([
            'getOrderTotal' => 42,
            'checkQuantities' => true,
        ]);

        $this->assertSame(42, $this->class->getRequest('checkout')['total']['amount']);
    }

    public function testWhenPostedDeliveryOptionIsNotAvailableTheCartIsNotChanged()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer(true, '3,');
        $this->cart->id_carrier = 0;
        $this->cart_adapter->shouldReceive('getDeliveryOptionList')
            ->once()
            ->with(1)
            ->andReturn([
                2 => [
                    '1,' => [],
                    '2,' => [],
                ],
                5 => [
                    '3,' => [],
                ],
            ]);
        $this->cart_adapter->shouldNotReceive('setDeliveryOption');
        $this->cart_adapter->shouldNotReceive('getOrderTotal');
        $this->cart_adapter->shouldReceive([
            'isVirtualCart' => false,
            'checkQuantities' => true,
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'The checkout cart has no carrier',
        ], $this->class->getRequest('checkout'));
        $this->logger->shouldHaveReceived('addLog')
            ->with('ApplepayPaymentMethod::getRequest() - The posted delivery option is not available for the checkout cart (cart id: 1)', 'error')
            ->once();
    }

    /**
     * @dataProvider malformedDeliveryOptionProvider
     *
     * @param mixed $delivery_option
     */
    public function testWhenPostedDeliveryOptionIsMalformedTheCartIsNotChanged($delivery_option)
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer(true, $delivery_option);
        $this->cart->id_carrier = 0;
        $this->cart_adapter->shouldNotReceive('getDeliveryOptionList');
        $this->cart_adapter->shouldNotReceive('setDeliveryOption');
        $this->cart_adapter->shouldNotReceive('getOrderTotal');
        $this->cart_adapter->shouldReceive([
            'isVirtualCart' => false,
            'checkQuantities' => true,
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'The checkout cart has no carrier',
        ], $this->class->getRequest('checkout'));
        $this->logger->shouldHaveReceived('addLog')
            ->with('ApplepayPaymentMethod::getRequest() - The posted delivery option is not available for the checkout cart (cart id: 1)', 'error')
            ->once();
    }

    public function malformedDeliveryOptionProvider()
    {
        yield 'without the trailing comma' => ['2'];

        yield 'not a carrier id' => ['abc,'];

        yield 'carrier ids without separator' => ['2,3'];

        yield 'injected markup' => ['2,<script>'];

        yield 'array' => [['2' => '2,']];
    }

    public function testWhenCheckoutCartHasNoDeliveryAddressThePostedDeliveryOptionIsIgnored()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer(true, '2,');
        $this->cart->id_address_delivery = 0;
        $this->cart->id_carrier = 0;
        $this->cart_adapter->shouldNotReceive('getDeliveryOptionList');
        $this->cart_adapter->shouldNotReceive('setDeliveryOption');
        $this->cart_adapter->shouldReceive([
            'checkQuantities' => true,
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'The checkout cart has no delivery address',
        ], $this->class->getRequest('checkout'));
    }

    public function testWhenPostedDeliveryOptionCanNotBeSaved()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer(true, '2,');
        $this->cart->id_carrier = 0;
        $this->cart->delivery_option = '';
        $this->cart_adapter->shouldReceive('getDeliveryOptionList')
            ->andReturn([
                2 => [
                    '2,' => [],
                ],
            ]);
        $this->cart_adapter->shouldReceive('setDeliveryOption')
            ->once()
            ->andReturn(false);
        $this->cart_adapter->shouldNotReceive('getOrderTotal');
        $this->cart_adapter->shouldReceive([
            'checkQuantities' => true,
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'The checkout cart delivery option can not be saved',
        ], $this->class->getRequest('checkout'));
        $this->logger->shouldHaveReceived('addLog')
            ->with('ApplepayPaymentMethod::getRequest() - The checkout cart delivery option can not be saved (cart id: 1)', 'error')
            ->once();
    }

    public function testWhenVirtualCheckoutCartHasNoCarrier()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->mockCheckoutCustomer(true);
        $this->cart->id_carrier = 0;
        $this->cart_adapter->shouldReceive('isVirtualCart')
            ->once()
            ->with($this->cart)
            ->andReturn(true);
        $this->cart_adapter->shouldReceive([
            'getOrderTotal' => 42,
            'checkQuantities' => true,
        ]);

        $this->assertSame([
            'country_code' => 'FR',
            'currency_code' => 'EUR',
            'total' => [
                'label' => 'my mock',
                'amount' => 42,
            ],
            'apple_pay_domain' => 'my-mock.com',
        ], $this->class->getRequest('checkout'));
    }

    public function testWhenCheckoutCartCheckIsDisabled()
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->cart->id_carrier = 0;
        $this->validate_adapter->shouldNotReceive('validate');
        $this->tools_adapter->shouldNotReceive('tool');
        $this->cart_adapter->shouldNotReceive('isVirtualCart');
        $this->cart_adapter->shouldNotReceive('setDeliveryOption');
        $this->cart_adapter->shouldReceive([
            'getOrderTotal' => 42,
            'checkQuantities' => true,
        ]);

        $this->assertSame([
            'country_code' => 'FR',
            'currency_code' => 'EUR',
            'total' => [
                'label' => 'my mock',
                'amount' => 42,
            ],
            'apple_pay_domain' => 'my-mock.com',
        ], $this->class->getRequest('checkout', false));
    }

    /**
     * @dataProvider nonCheckoutWorkflowProvider
     *
     * @param string $workflow
     */
    public function testWhenNonCheckoutCartHasNoCustomerAddressOrCarrier($workflow)
    {
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->cart->id_customer = 0;
        $this->cart->id_address_delivery = 0;
        $this->cart->id_address_invoice = 0;
        $this->cart->id_carrier = 0;
        $this->validate_adapter->shouldNotReceive('validate');
        $this->cart_adapter->shouldNotReceive('isVirtualCart');
        $this->cart_adapter->shouldNotReceive('setDeliveryOption');
        $this->tools_adapter->shouldNotReceive('tool')
            ->with('getValue', 'delivery_option');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'empty_cart')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'address')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'carrier')
            ->andReturn(false);
        $this->cart_adapter->shouldReceive([
            'getOrderTotal' => 42,
            'checkQuantities' => true,
        ]);
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
        ], $this->class->getRequest($workflow));
    }

    public function nonCheckoutWorkflowProvider()
    {
        yield 'shopping cart' => ['shopping-cart'];

        yield 'product page without a new cart' => ['product'];
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
     * @description Mock the context customer and the delivery option posted with a checkout request
     *
     * @param bool $is_loaded
     * @param mixed $delivery_option
     */
    private function mockCheckoutCustomer($is_loaded, $delivery_option = false)
    {
        $this->validate_adapter->shouldReceive('validate')
            ->with('isLoadedObject', $this->context->customer)
            ->andReturn($is_loaded);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'delivery_option')
            ->andReturn($delivery_option);
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
