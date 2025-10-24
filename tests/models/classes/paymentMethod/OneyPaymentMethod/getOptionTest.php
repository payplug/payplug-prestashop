<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getOptionTest extends BaseOneyPaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenConfigurationIsntValidArrayFormat($current_configuration)
    {
        $this->assertSame([], $this->class->getOption($current_configuration));
    }

    public function testWhenOneyCtaIsAllowed()
    {
        $current_configuration = [];

        $this->configuration->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('FR');

        $this->configuration->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn(true);

        $this->class->shouldReceive('getProductCallToAction')
            ->with('oney_product_animation')
            ->andReturn([
                'name' => 'oney_product_animation',
                'image_url' => 'modules/payplug/views/img/oney/payplug-productOneyCta.jpg',
                'title' => 'oneyPopupProduct.title',
                'switch' => true,
                'checked' => false,
            ]);

        $this->class->shouldReceive('getCartCallToAction')
            ->with('oney_cart_animation')
            ->andReturn([
                'name' => 'oney_cart_animation',
                'image_url' => 'modules/payplug/views/img/oney/payplug-cartOneyCta.jpg',
                'title' => 'oneyPopupCart.title',
                'switch' => true,
                'checked' => false,
            ]);

        $expected = [
            'name' => 'paymentMethodsBlock',
            'title' => 'paylater.title',
            'descriptions' => [
                'live' => [
                    'description' => 'paylater.description',
                ],
                'sandbox' => [
                    'description' => 'paylater.description',
                ],
            ],
            'options' => [
                'name' => 'oney',
                'title' => 'paylater.options.title',
                'image' => 'assets/images/lg-oney.png',
                'checked' => false,
                'descriptions' => [
                    'live' => [
                        'description' => 'paylater.options.description',
                        'link_know_more' => [
                            'text' => 'paylater.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/360013071080',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => 'paylater.options.description',
                        'link_know_more' => [
                            'text' => 'paylater.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/360013071080',
                            'target' => '_blank',
                        ],
                    ],
                    'advanced' => [
                        'description' => 'paylater.advanced',
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_oney',
                        'className' => '_paylaterLabel',
                        'label' => 'paylater.options.with_fees.label',
                        'subText' => 'paylater.options.with_fees.subtext',
                        'value' => 1,
                        'checked' => true,
                    ],
                    [
                        'name' => 'payplug_oney',
                        'className' => '_paylaterLabel',
                        'label' => 'paylater.options.without_fees.label',
                        'subText' => 'paylater.options.without_fees.subtext',
                        'value' => 0,
                        'checked' => false,
                    ],
                ],
                'advanced_options' => [
                    [
                        'name' => 'thresholds',
                        'image_url' => 'modules/payplug/views/img/oney/payplug-thresholds.jpg',
                        'title' => 'thresholds.title',
                        'descriptions' => [
                            'description' => 'thresholds.description',
                            'min_amount' => [
                                'name' => 'oney_min_amounts',
                                'value' => 0,
                                'placeholder' => 0,
                                'default' => 0,
                            ],
                            'inter' => 'thresholds.inter',
                            'max_amount' => [
                                'name' => 'oney_max_amounts',
                                'value' => 0,
                                'placeholder' => 0,
                                'default' => 0,
                            ],
                            'error' => [
                                'text' => 'thresholds.error.text',
                                'maxtext' => 'thresholds.error.max.text',
                                'mintext' => 'thresholds.error.min.text',
                            ],
                        ],
                        'switch' => false,
                    ],
                    [
                        'name' => 'oney_schedule',
                        'image_url' => 'modules/payplug/views/img/oney/payplug-optimized.jpg',
                        'title' => 'oneySchedule.title',
                        'descriptions' => [
                            [
                                'description' => 'oneySchedule.description',
                                'link_know_more' => [
                                    'text' => 'paylater.link',
                                    'url' => 'https://support.payplug.com/hc/fr/articles/360013071080#h_2595dd3d-a281-43ab-a51a-4986fecde5ee',
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                        'switch' => true,
                        'checked' => false,
                    ],
                    [
                        'name' => 'oney_product_animation',
                        'image_url' => 'modules/payplug/views/img/oney/payplug-productOneyCta.jpg',
                        'title' => 'oneyPopupProduct.title',
                        'switch' => true,
                        'checked' => false,
                    ],
                    [
                        'name' => 'oney_cart_animation',
                        'image_url' => 'modules/payplug/views/img/oney/payplug-cartOneyCta.jpg',
                        'title' => 'oneyPopupCart.title',
                        'switch' => true,
                        'checked' => false,
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $this->class->getOption($current_configuration));
    }

    public function testWhenOneyCtaIsntAllowed()
    {
        $current_configuration = [];

        $this->configuration->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('BE');

        $this->configuration->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn(true);

        $this->class->shouldReceive('getThresholds')
            ->with('')
            ->andReturn([]);

        $this->class->shouldReceive('getSchedule')
            ->with('')
            ->andReturn([]);

        $this->class->shouldReceive('getProductCallToAction')
            ->with('')
            ->andReturn([]);

        $this->class->shouldReceive('getCartCallToAction')
            ->with('')
            ->andReturn([]);

        $expected = [
            [
                'name' => 'thresholds',
                'image_url' => 'modules/payplug/views/img/oney/payplug-thresholds.jpg',
                'title' => 'thresholds.title',
                'descriptions' => [
                    'description' => 'thresholds.description',
                    'min_amount' => [
                        'name' => 'oney_min_amounts',
                        'value' => 0,
                        'placeholder' => 0,
                        'default' => 0,
                    ],
                    'inter' => 'thresholds.inter',
                    'max_amount' => [
                        'name' => 'oney_max_amounts',
                        'value' => 0,
                        'placeholder' => 0,
                        'default' => 0,
                    ],
                    'error' => [
                        'text' => 'thresholds.error.text',
                        'maxtext' => 'thresholds.error.max.text',
                        'mintext' => 'thresholds.error.min.text',
                    ],
                ],
                'switch' => false,
            ],
        ];

        $this->assertSame(
            $expected,
            $this->class->getOption($current_configuration)['options']['advanced_options']
        );
    }
}
