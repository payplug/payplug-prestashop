<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group applepay_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
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
            'applepay_display' => '{"checkout":true,"cart":false,"product":false}',
        ];
        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'IOptions',
                'name' => 'applepay_display',
                'title' => 'paymentmethods.applepay.display.title',
                'multiple' => true,
                'options' => [
                    [
                        'name' => 'applepay_display',
                        'label' => 'paymentmethods.applepay.display.checkout',
                        'value' => 'checkout',
                        'checked' => true,
                    ],
                    [
                        'name' => 'applepay_display',
                        'label' => 'paymentmethods.applepay.display.cart',
                        'value' => 'cart',
                        'checked' => false,
                    ],
                    [
                        'name' => 'applepay_display',
                        'label' => 'paymentmethods.applepay.display.product',
                        'value' => 'product',
                        'checked' => false,
                    ],
                ],
                'carriers' => [],
            ],
        ];
        $this->assertSame(
            $expected,
            $this->classe->getOption($configuration)['options']
        );
    }

    public function testWhenCarrierIsReturned()
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
            'applepay_display' => '{"checkout":true,"cart":false,"product":false}',
        ];

        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'IOptions',
                'name' => 'applepay_display',
                'title' => 'paymentmethods.applepay.display.title',
                'multiple' => true,
                'options' => [
                    [
                        'name' => 'applepay_display',
                        'label' => 'paymentmethods.applepay.display.checkout',
                        'value' => 'checkout',
                        'checked' => true,
                    ],
                    [
                        'name' => 'applepay_display',
                        'label' => 'paymentmethods.applepay.display.cart',
                        'value' => 'cart',
                        'checked' => false,
                    ],
                    [
                        'name' => 'applepay_display',
                        'label' => 'paymentmethods.applepay.display.product',
                        'value' => 'product',
                        'checked' => false,
                    ],
                ],
                'carriers' => [
                    'title' => 'paymentmethods.applepay.carrier.title',
                    'alert' => 'paymentmethods.applepay.carrier.alert',
                    'description' => 'paymentmethods.applepay.carrier.description',
                    'instructions' => 'paymentmethods.applepay.carrier.instructions',
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
