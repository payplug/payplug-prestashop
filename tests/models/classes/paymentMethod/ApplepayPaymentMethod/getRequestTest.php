<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getRequestTest extends BaseApplepayPaymentMethod
{
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
            $this->class->getRequest()
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
}
