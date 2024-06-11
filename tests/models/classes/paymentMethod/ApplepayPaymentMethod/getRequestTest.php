<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group applepay_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getRequestTest extends BaseApplepayPaymentMethod
{
    public function testWhenWorkflowIsntFromCheckout()
    {
        $this->currency_adapter
            ->shouldReceive([
                'get' => CurrencyMock::get(),
            ]);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'carrier')
            ->andReturn(false);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'workflow')
            ->andReturn('shopping-cart');
        $this->cart_adapter
            ->shouldReceive([
                'getOrderTotal' => 42,
            ]);
        $this->classe
            ->shouldReceive([
                'getDeliveryOptions' => [
                    [
                        'identifier' => '42',
                        'label' => 'carrier label',
                        'detail' => 'carrier detail',
                        'amount' => 42,
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
            'countryCode' => 'FR',
            'currencyCode' => 'EUR',
            'merchantCapabilities' => [
                'supports3DS',
            ],
            'supportedNetworks' => [
                'visa',
                'masterCard',
                'discover',
            ],
            'total' => [
                'label' => 'my mock',
                'type' => 'final',
                'amount' => 42,
            ],
            'applicationData' => 'eyJhcHBsZV9wYXlfZG9tYWluIjoibXktbW9jay5jb20ifQ==',
            'shippingMethods' => [
                [
                    'identifier' => '42',
                    'label' => 'carrier label',
                    'detail' => 'carrier detail',
                    'amount' => 42,
                ],
            ],
            'requiredBillingContactFields' => [
                'postalAddress',
                'name',
            ],
            'requiredShippingContactFields' => [
                'email',
                'name',
                'phone',
                'postalAddress',
            ],
            'lineItems' => [
                [
                    'label' => 'line item',
                    'type' => 'final',
                    'amount' => 42,
                ],
            ],
        ];
        $this->assertSame($expected, $this->classe->getRequest());
    }

    public function testWhenWorkflowIsFromCheckout()
    {
        $this->currency_adapter
            ->shouldReceive([
                'get' => CurrencyMock::get(),
            ]);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'carrier')
            ->andReturn(false);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'workflow')
            ->andReturn('checkout');
        $this->cart_adapter
            ->shouldReceive([
                'getOrderTotal' => 42,
            ]);
        $this->classe
            ->shouldReceive([
                'getDeliveryOptions' => [
                    [
                        'identifier' => '42',
                        'label' => 'carrier label',
                        'detail' => 'carrier detail',
                        'amount' => 42,
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
            'countryCode' => 'FR',
            'currencyCode' => 'EUR',
            'merchantCapabilities' => [
                'supports3DS',
            ],
            'supportedNetworks' => [
                'visa',
                'masterCard',
                'discover',
            ],
            'total' => [
                'label' => 'my mock',
                'type' => 'final',
                'amount' => 42,
            ],
            'applicationData' => 'eyJhcHBsZV9wYXlfZG9tYWluIjoibXktbW9jay5jb20ifQ==',
        ];
        $this->assertSame($expected, $this->classe->getRequest());
    }

    /**
     * @description Test for the getRequest method when the cart ID is null.
     */
    public function testWhenWorkflowIsProductAndEmptyCartIsTrue()
    {
        $this->currency_adapter
            ->shouldReceive([
                                'get' => CurrencyMock::get(),
                            ]);

        $this->address_adapter
            ->shouldReceive('getFirstCustomerAddressId')
            ->andReturn(1);

        $this->cart_adapter
            ->shouldReceive('createNewCart')
            ->andReturn((object) ['id' => 1, 'id_address_delivery' => 42, 'id_address_invoice' => 42]);
        $this->cart_rule_adapter
            ->shouldReceive('autoAddToCart');

        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'quantity')
            ->andReturn(2);
        $this->cart_adapter
            ->shouldReceive('updateQty')
            ->with(1, 2, 123);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'empty_cart')
            ->andReturn(true);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'id_product')
            ->andReturn(123);

        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'workflow')
            ->andReturn('product');
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('getValue', 'carrier')
            ->andReturn(false);

        $this->cart_adapter
            ->shouldReceive(
                [
                    'update' => true,
                ]
            );

        $this->cart_adapter
            ->shouldReceive('updateAddressId')
            ->with(1, $this->context->cart->id_address_delivery, $this->context->cart->id_address_delivery);
        $this->cart_adapter
            ->shouldReceive('getOrderTotal')
            ->andReturn(42);
        $this->classe
            ->shouldReceive('getDeliveryOptions')
            ->andReturn([
                            [
                                'identifier' => '42',
                                'label' => 'carrier label',
                                'detail' => 'carrier detail',
                                'amount' => 42,
                            ],
                        ]);
        $this->classe
            ->shouldReceive('getLinesItems')
            ->andReturn([
                            [
                                'label' => 'line item',
                                'type' => 'final',
                                'amount' => 42,
                            ],
                        ]);

        $this->context->cookie
            ->shouldReceive(['write' => true]);

        $expected = [
            'countryCode' => 'FR',
            'currencyCode' => 'EUR',
            'merchantCapabilities' => [
                'supports3DS',
            ],
            'supportedNetworks' => [
                'visa',
                'masterCard',
                'discover',
            ],
            'total' => [
                'label' => 'my mock',
                'type' => 'final',
                'amount' => 42,
            ],
            'applicationData' => 'eyJhcHBsZV9wYXlfZG9tYWluIjoibXktbW9jay5jb20ifQ==',
            'shippingMethods' => [
                [
                    'identifier' => '42',
                    'label' => 'carrier label',
                    'detail' => 'carrier detail',
                    'amount' => 42,
                ],
            ],
            'requiredBillingContactFields' => [
                'postalAddress',
                'name',
            ],
            'requiredShippingContactFields' => [
                'email',
                'name',
                'phone',
                'postalAddress',
            ],
            'lineItems' => [
                [
                    'label' => 'line item',
                    'type' => 'final',
                    'amount' => 42,
                ],
            ],
        ];

        $this->assertSame($expected, $this->classe->getRequest());
    }
}
