<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group debug
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoCarriersReturned()
    {
        $this->classe
            ->shouldReceive([
                'getAvailableCarriers' => [],
            ]);
        $configuration = [
            'applepay_cart' => true,
            'applepay_checkout' => true,
        ];
        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'applepay_checkout',
                'title' => 'paymentmethods.applepay.checkout.title',
                'checked' => true,
            ],
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'applepay_cart',
                'title' => 'paymentmethods.applepay.cart.title',
                'descriptions' => [
                    'live' => [
                        'description' => 'paymentmethods.applepay.cart.description',
                    ],
                    'sandbox' => [
                        'description' => 'paymentmethods.applepay.cart.description',
                    ],
                ],
                'checked' => true,
                'carriers' => [],
            ],
        ];
        $this->assertSame(
            $expected,
            $this->classe->getOption($configuration)['options']
        );
    }

    public function testWhenNoCarrierReturn()
    {
        $carriers = [
            [
                'id_carrier' => 42,
                'name' => 'carrier',
                'checked' => true,
            ],
        ];
        $this->classe
            ->shouldReceive([
                'getAvailableCarriers' => $carriers,
            ]);
        $configuration = [
            'applepay_cart' => true,
            'applepay_checkout' => true,
        ];

        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'applepay_checkout',
                'title' => 'paymentmethods.applepay.checkout.title',
                'checked' => true,
            ],
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'applepay_cart',
                'title' => 'paymentmethods.applepay.cart.title',
                'descriptions' => [
                    'live' => [
                        'description' => 'paymentmethods.applepay.cart.description',
                    ],
                    'sandbox' => [
                        'description' => 'paymentmethods.applepay.cart.description',
                    ],
                ],
                'checked' => true,
                'carriers' => [
                    'title' => 'paymentmethods.applepay.carrier.title',
                    'alert' => 'paymentmethods.applepay.carrier.alert',
                    'description' => 'paymentmethods.applepay.carrier.description',
                    'carriers_list' => $carriers,
                ],
            ],
        ];
        $this->assertSame(
            $expected,
            $this->classe->getOption($configuration)['options']
        );
    }
}
