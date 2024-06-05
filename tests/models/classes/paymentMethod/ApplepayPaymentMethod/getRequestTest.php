<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group applepay_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
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
}
