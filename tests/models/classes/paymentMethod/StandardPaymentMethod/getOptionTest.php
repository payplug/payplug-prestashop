<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseStandardPaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenConfigurationIsNotValidArrayFormat($current_configuration)
    {
        $this->assertSame([], $this->classe->getOption($current_configuration));
    }

    public function testWhenOnlyStandardPaymentExpected()
    {
        $current_configuration = [
            'embedded_mode' => 'redirect',
            'one_click' => true,
        ];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => false,
            ]);
        $this->dependencies->configClass = $configClass;

        $expected = [
            'type' => 'payment_method',
            'name' => 'standard',
            'title' => 'paymentmethods.standard.title',
            'image' => 'modules/payplug/views/img/svg/payment/standard.svg',
            'checked' => true,
            'available_test_mode' => true,
            'descriptions' => [
                'live' => [
                    'description' => 'paymentmethods.standard.descriptions.live',
                    'link_know_more' => [],
                    'advanced_options' => 'paymentmethods.standard.advanced',
                ],
                'sandbox' => [
                    'description' => 'paymentmethods.standard.descriptions.live',
                    'link_know_more' => [],
                    'advanced_options' => 'paymentmethods.standard.advanced',
                ],
            ],
            'options' => [
                [
                    'type' => 'payment_option',
                    'sub_type' => 'IOptions',
                    'name' => 'embeded',
                    'title' => 'paymentmethods.embedded.title',
                    'descriptions' => [
                        'live' => [
                            'description_popup' => 'paymentmethods.embedded.descriptions.popup.text',
                            'description_redirect' => 'paymentmethods.embedded.descriptions.redirect.text',
                            'description_integrated' => 'paymentmethods.embedded.descriptions.integrated.text',
                            'link_know_more' => [
                                'text' => 'paymentmethods.embedded.link',
                                'url' => 'https://support.payplug.com/hc/fr/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                        'sandbox' => [
                            'description_popup' => 'paymentmethods.embedded.descriptions.popup.text',
                            'description_redirect' => 'paymentmethods.embedded.descriptions.redirect.text',
                            'description_integrated' => 'paymentmethods.embedded.descriptions.integrated.text',
                            'link_know_more' => [
                                'text' => 'paymentmethods.embedded.link',
                                'url' => 'https://support.payplug.com/hc/fr/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                    ],
                    'options' => [
                        [
                            'name' => 'payplug_embedded',
                            'label' => 'paymentmethods.embedded.options.popup',
                            'value' => 'popup',
                            'checked' => false,
                        ],
                        [
                            'name' => 'payplug_embedded',
                            'label' => 'paymentmethods.embedded.options.redirect',
                            'value' => 'redirect',
                            'checked' => true,
                        ],
                    ],
                ],
                [
                    'type' => 'warning_message',
                    'sub_type' => 'warning',
                    'name' => 'warning_message',
                    'payment_method' => 'integrated',
                    'description_title' => 'paymentmethods.integrated.alert.text.title',
                    'description' => 'paymentmethods.integrated.alert.text',
                ],
                [
                    'type' => 'payment_option',
                    'sub_type' => 'switch',
                    'name' => 'one_click',
                    'title' => 'paymentmethods.one_click.title',
                    'descriptions' => [
                        'live' => [
                            'description' => 'paymentmethods.one_click.descriptions.live',
                            'link_know_more' => [
                                'text' => 'paymentmethods.one_click.link',
                                'url' => 'https://support.payplug.com/hc/fr/articles/360022213892',
                                'target' => '_blank',
                            ],
                        ],
                        'sandbox' => [
                            'description' => 'paymentmethods.one_click.descriptions.live',
                            'link_know_more' => [
                                'text' => 'paymentmethods.one_click.link',
                                'url' => 'https://support.payplug.com/hc/fr/articles/360022213892',
                                'target' => '_blank',
                            ],
                        ],
                    ],
                    'checked' => true,
                ],
            ],
            'advanced_settings' => [],
        ];

        $this->assertSame($expected, $this->classe->getOption($current_configuration));
    }

    public function testWhenIntegratedPaymentExpected()
    {
        $current_configuration = [
            'embedded_mode' => 'redirect',
            'one_click' => true,
        ];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($feature) {
                return 'feature_integrated' == $feature;
            });
        $this->dependencies->configClass = $configClass;

        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'IOptions',
                'name' => 'embeded',
                'title' => 'paymentmethods.embedded.title',
                'descriptions' => [
                    'live' => [
                        'description_popup' => 'paymentmethods.embedded.descriptions.popup.text',
                        'description_redirect' => 'paymentmethods.embedded.descriptions.redirect.text',
                        'description_integrated' => 'paymentmethods.embedded.descriptions.integrated.text',
                        'link_know_more' => [
                            'text' => 'paymentmethods.embedded.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/4409698334098',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description_popup' => 'paymentmethods.embedded.descriptions.popup.text',
                        'description_redirect' => 'paymentmethods.embedded.descriptions.redirect.text',
                        'description_integrated' => 'paymentmethods.embedded.descriptions.integrated.text',
                        'link_know_more' => [
                            'text' => 'paymentmethods.embedded.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/4409698334098',
                            'target' => '_blank',
                        ],
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_embedded',
                        'label' => 'paymentmethods.embedded.options.integrated',
                        'value' => 'integrated',
                        'checked' => false,
                    ],
                    [
                        'name' => 'payplug_embedded',
                        'label' => 'paymentmethods.embedded.options.popup',
                        'value' => 'popup',
                        'checked' => false,
                    ],
                    [
                        'name' => 'payplug_embedded',
                        'label' => 'paymentmethods.embedded.options.redirect',
                        'value' => 'redirect',
                        'checked' => true,
                    ],
                ],
            ],
            [
                'type' => 'warning_message',
                'sub_type' => 'warning',
                'name' => 'warning_message',
                'payment_method' => 'integrated',
                'description_title' => 'paymentmethods.integrated.alert.text.title',
                'description' => 'paymentmethods.integrated.alert.text',
            ],
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'one_click',
                'title' => 'paymentmethods.one_click.title',
                'descriptions' => [
                    'live' => [
                        'description' => 'paymentmethods.one_click.descriptions.live',
                        'link_know_more' => [
                            'text' => 'paymentmethods.one_click.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/360022213892',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => 'paymentmethods.one_click.descriptions.live',
                        'link_know_more' => [
                            'text' => 'paymentmethods.one_click.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/360022213892',
                            'target' => '_blank',
                        ],
                    ],
                ],
                'checked' => true,
            ],
        ];

        $this->assertSame($expected, $this->classe->getOption($current_configuration)['options']);
    }

    public function testWhenInstallmentPaymentExpected()
    {
        $current_configuration = [
            'embedded_mode' => 'redirect',
            'one_click' => true,
            'installment' => true,
            'inst_mode' => 2,
            'inst_min_amount' => 150,
        ];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($feature) {
                return 'feature_installment' == $feature;
            });
        $this->dependencies->configClass = $configClass;

        $expected = [
            'title' => 'paymentmethods.standard.advanced',
            'options' => [
                [
                    'name' => 'fractional',
                    'title' => 'paymentmethods.installment.title',
                    'class' => '-installment',
                    'enabled' => [
                        'name' => 'payplug_inst',
                        'checked' => true,
                    ],
                    'descriptions' => [
                        'live' => [
                            'description_1' => 'paymentmethods.installment.descriptions.description_1',
                            'text_from' => 'paymentmethods.installment.descriptions.text_from',
                            'description_2' => 'paymentmethods.installment.descriptions.description_2',
                            'links' => [
                                0 => [
                                    'text' => 'paymentmethods.installment.descriptions.controller_link',
                                    'url' => 'link',
                                    'target' => '_blank',
                                    'data_e2e' => 'data-panelInstallmentLink',
                                ],
                                1 => [
                                    'text' => 'paymentmethods.installment.link',
                                    'url' => 'https://support.payplug.com/hc/fr/articles/360022447972',
                                    'target' => '_blank',
                                ],
                            ],
                            'notes' => [
                                'type' => '-warning',
                                'description' => 'paymentmethods.installment.descriptions.alert.start<br />paymentmethods.installment.descriptions.alert.end',
                            ],
                        ],
                        'sandbox' => [
                            'description_1' => 'paymentmethods.installment.descriptions.description_1',
                            'text_from' => 'paymentmethods.installment.descriptions.text_from',
                            'description_2' => 'paymentmethods.installment.descriptions.description_2',
                            'links' => [
                                0 => [
                                    'text' => 'paymentmethods.installment.descriptions.controller_link',
                                    'url' => 'link',
                                    'target' => '_blank',
                                    'data_e2e' => 'data-panelInstallmentLink',
                                ],
                                1 => [
                                    'text' => 'paymentmethods.installment.link',
                                    'url' => 'https://support.payplug.com/hc/fr/articles/360022447972',
                                    'target' => '_blank',
                                ],
                            ],
                            'notes' => [
                                'type' => '-warning',
                                'description' => 'paymentmethods.installment.descriptions.alert.start<br />paymentmethods.installment.descriptions.alert.end',
                            ],
                        ],
                    ],
                    'options' => [
                        [
                            'name' => 'payplug_inst_mode',
                            'type' => 'select',
                            'disabled' => false,
                            'options' => [
                                [
                                    'value' => 2,
                                    'label' => 'paymentmethods.installment.select.2_schedules',
                                    'checked' => true,
                                ],
                                [
                                    'value' => 3,
                                    'label' => 'paymentmethods.installment.select.3_schedules',
                                    'checked' => false,
                                ],
                                [
                                    'value' => 4,
                                    'label' => 'paymentmethods.installment.select.4_schedules',
                                    'checked' => false,
                                ],
                            ],
                        ],
                        [
                            'type' => 'input',
                            'name' => 'payplug_inst_min_amount',
                            'disabled' => false,
                            'value' => 150,
                            'min' => 4,
                            'step' => 1,
                            'max' => 20000,
                            'out_of_bound_msg' => 'paymentmethods.installment.error_limit',
                        ],
                    ],
                    'notes' => [
                        'type' => '-warning',
                        'description' => [
                            'start' => 'paymentmethods.installment.descriptions.alert.start',
                            'end' => 'paymentmethods.installment.descriptions.alert.end',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $this->classe->getOption($current_configuration)['advanced_settings']);
    }

    public function testWhenDeferredPaymentExpected()
    {
        $current_configuration = [
            'embedded_mode' => 'redirect',
            'one_click' => true,
            'deferred' => true,
            'deferred_state' => 2,
        ];

        $this->classe->shouldReceive([
            'getDeferredState' => [],
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($feature) {
                return 'feature_deferred' == $feature;
            });
        $this->dependencies->configClass = $configClass;

        $expected = [
            'title' => 'paymentmethods.standard.advanced',
            'options' => [
                [
                    'name' => 'deferred',
                    'title' => 'paymentmethods.deferred.title',
                    'class' => '-deferred',
                    'enabled' => [
                        'name' => 'payplug_deferred',
                        'checked' => true,
                    ],
                    'descriptions' => [
                        'live' => [
                            'description_1' => 'paymentmethods.deferred.descriptions.description_1',
                            'description_2' => 'paymentmethods.deferred.descriptions.description_2',
                            'links' => [
                                [
                                    'text' => 'paymentmethods.deferred.link',
                                    'url' => 'https://support.payplug.com/hc/fr/articles/360010088420',
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                        'sandbox' => [
                            'description_1' => 'paymentmethods.deferred.descriptions.description_1',
                            'description_2' => 'paymentmethods.deferred.descriptions.description_2',
                            'links' => [
                                [
                                    'text' => 'paymentmethods.deferred.link',
                                    'url' => 'https://support.payplug.com/hc/fr/articles/360010088420',
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'disabled' => false,
                        'name' => 'payplug_deferred_state',
                        'type' => 'select',
                        'options' => [],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $this->classe->getOption($current_configuration)['advanced_settings']);
    }
}
