<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoCarriersReturned()
    {
        $this->class->shouldReceive([
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
                        'name' => 'applepay_checkout',
                        'image_url' => 'modules/payplug/views/img/applepay/checkoutCta.jpg',
                        'title' => 'paymentmethods.applepay.display.checkout',
                        'switch' => true,
                        'checked' => true,
                    ],
                    [
                        'name' => 'applepay_cart',
                        'image_url' => 'modules/payplug/views/img/applepay/cartCta.jpg',
                        'title' => 'paymentmethods.applepay.display.cart',
                        'switch' => true,
                        'checked' => false,
                    ],
                    [
                        'name' => 'applepay_product',
                        'image_url' => 'modules/payplug/views/img/applepay/productCta.jpg',
                        'title' => 'paymentmethods.applepay.display.product',
                        'switch' => true,
                        'checked' => false,
                    ],
                ],
                'carriers' => [],
            ],
        ];
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame(
            $expected,
            $this->class->getOption($configuration)['options']
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
        $this->class->shouldReceive([
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
                        'name' => 'applepay_checkout',
                        'image_url' => 'modules/payplug/views/img/applepay/checkoutCta.jpg',
                        'title' => 'paymentmethods.applepay.display.checkout',
                        'switch' => true,
                        'checked' => true,
                    ],
                    [
                        'name' => 'applepay_cart',
                        'image_url' => 'modules/payplug/views/img/applepay/cartCta.jpg',
                        'title' => 'paymentmethods.applepay.display.cart',
                        'switch' => true,
                        'checked' => false,
                    ],
                    [
                        'name' => 'applepay_product',
                        'image_url' => 'modules/payplug/views/img/applepay/productCta.jpg',
                        'title' => 'paymentmethods.applepay.display.product',
                        'switch' => true,
                        'checked' => false,
                    ],
                ],
                'carriers' => [
                    'title' => 'paymentmethods.applepay.carrier.title',
                    'alert' => 'paymentmethods.applepay.carrier.alert',
                    'descriptions' => [
                        'live' => [
                            'description' => 'paymentmethods.applepay.carrier.description',
                            'description_bold' => 'paymentmethods.applepay.carrier.description_bold',
                            'description_warning' => 'paymentmethods.applepay.carrier.description_warning',
                        ],
                        'sandbox' => [],
                    ],
                    'instructions' => 'paymentmethods.applepay.carrier.instructions',
                    'carriers_list' => $carriers,
                ],
            ],
        ];

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame(
            $expected,
            $this->class->getOption($configuration)['options']
        );
    }
}
