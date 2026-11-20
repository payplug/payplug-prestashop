<?php

namespace PayPlug\tests\models\classes\paymentMethod\ScalapayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group scalapay_payment_method_class
 */
class getOptionTest extends BaseScalapayPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }

    public function testThresholdsUseTheFieldsOfTheOtherPproMethods()
    {
        $this->assertSame(
            [
                [
                    'name' => 'thresholds',
                    'image_url' => 'modules/payplug/views/img/oney/payplug-thresholds.jpg',
                    'title' => 'paymentmethods.apm.thresholds.title',
                    'descriptions' => [
                        'description' => 'paymentmethods.apm.thresholds.description',
                        'decimal' => true,
                        'strict_range' => true,
                        'min_amount' => [
                            'name' => 'scalapay_min_amounts',
                            'label' => 'paymentmethods.apm.thresholds.min',
                            'value' => 5.0,
                            'placeholder' => 5.0,
                            'default' => 5.0,
                        ],
                        'inter' => 'paymentmethods.apm.thresholds.inter',
                        'max_amount' => [
                            'name' => 'scalapay_max_amounts',
                            'label' => 'paymentmethods.apm.thresholds.max',
                            'value' => 4000.0,
                            'placeholder' => 4000.0,
                            'default' => 4000.0,
                        ],
                        'error' => [
                            'text' => 'paymentmethods.apm.thresholds.error.text',
                            'maxtext' => 'paymentmethods.apm.thresholds.error.max.text',
                            'mintext' => 'paymentmethods.apm.thresholds.error.min.text',
                        ],
                    ],
                    'switch' => false,
                ],
            ],
            array_slice($this->class->getOption([])['advanced_options'], 0, 1)
        );
    }

    public function testThresholdsShowTheMerchantRangeOfTheGenericSettings()
    {
        $this->stubLegacyCustomLimits('EUR:1000', 'EUR:200000');
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"scalapay":{"min":{"EUR":3000}}}');

        $descriptions = $this->class->getOption([])['advanced_options'][0]['descriptions'];

        $this->assertSame(30.0, $descriptions['min_amount']['value']);
        $this->assertSame(5.0, $descriptions['min_amount']['default']);
        $this->assertSame(4000.0, $descriptions['max_amount']['value']);
        $this->assertSame(4000.0, $descriptions['max_amount']['default']);
    }

    public function testThresholdsAreConvertedInTheBackOfficeCurrency()
    {
        $this->stubLegacyCustomLimits('', '');

        $this->class->getOption([]);

        $this->amountHelper->shouldHaveReceived('convertAmount')->with(500, true, 'EUR');
        $this->amountHelper->shouldHaveReceived('convertAmount')->with(400000, true, 'EUR');
        $this->amountHelper->shouldNotHaveReceived('convertAmount', [\Mockery::any(), true, '']);
        $this->addToAssertionCount(1);
    }

    public function testABoundTheApiDoesNotSetIsLeftEmpty()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn('{"scalapay":{"min":"EUR:500"}}');
        $translation = $this->translation->getPaymentMethodsTranslations();
        $translation['apm']['thresholds']['error']['default'] = 'between %s and %s';
        $this->class->set('translation', $translation);

        $descriptions = $this->class->getOption([])['advanced_options'][0]['descriptions'];

        $this->assertSame(5.0, $descriptions['min_amount']['default']);
        $this->assertSame(
            ['value' => null, 'placeholder' => null, 'default' => null],
            array_intersect_key($descriptions['max_amount'], array_flip(['value', 'placeholder', 'default']))
        );
        $this->assertSame('between 5 and -', $descriptions['error']['text']);
    }

    public function testCountriesFieldIsAddedAlongsideTheAmountFields()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{"scalapay":["IT"]}');
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn([10 => ['iso_code' => 'IT', 'name' => 'Italie']]);

        $option = $this->class->getOption([]);

        $this->assertSame('thresholds', $option['advanced_options'][0]['name']);
        $this->assertSame('scalapay_min_amounts', $option['advanced_options'][0]['descriptions']['min_amount']['name']);
        $this->assertSame('scalapay_countries', $option['countries']['name']);
        $this->assertSame(['IT'], $option['countries']['value']);
    }

    public function testTheWidgetOptionsFollowTheThresholdsInTheOneyOrder()
    {
        $names = array_column($this->class->getOption([])['advanced_options'], 'name');

        $this->assertSame(
            ['thresholds', 'scalapay_checkout_widget', 'scalapay_product_widget', 'scalapay_cart_widget', 'scalapay_amount_selectors'],
            $names
        );
    }

    public function testTheWidgetOptionsAreShownWithoutAccountLimits()
    {
        $this->configuration->shouldReceive('getValue')->with('amounts')->andReturn('{}');

        $names = array_column($this->class->getOption([])['advanced_options'], 'name');

        $this->assertSame(
            ['scalapay_checkout_widget', 'scalapay_product_widget', 'scalapay_cart_widget', 'scalapay_amount_selectors'],
            $names
        );
    }

    public function testEachWidgetSwitchHasATitleADescriptionAndNoIllustration()
    {
        $options = $this->getAdvancedOptionsByName([]);

        foreach (['checkout', 'product', 'cart'] as $page) {
            $this->assertSame(
                [
                    'name' => 'scalapay_' . $page . '_widget',
                    'title' => 'paymentmethods.scalapay.widget.' . $page . '.title',
                    'descriptions' => [
                        ['description' => 'paymentmethods.scalapay.widget.' . $page . '.description'],
                    ],
                    'switch' => true,
                    'checked' => true,
                ],
                $options['scalapay_' . $page . '_widget']
            );
        }
    }

    public function testTheSwitchesFollowTheConfiguration()
    {
        $options = $this->getAdvancedOptionsByName([
            'scalapay_checkout_widget' => true,
            'scalapay_product_widget' => false,
            'scalapay_cart_widget' => false,
        ]);

        $this->assertTrue($options['scalapay_checkout_widget']['checked']);
        $this->assertFalse($options['scalapay_product_widget']['checked']);
        $this->assertFalse($options['scalapay_cart_widget']['checked']);
    }

    public function testTheSelectorsOptionHasOneTextFieldPerPage()
    {
        $this->widget_action->shouldReceive('getStoredAmountSelectors')->with('product')->andReturn(['#price', '.price']);

        $option = $this->getAdvancedOptionsByName([])['scalapay_amount_selectors'];

        $this->assertSame('paymentmethods.scalapay.widget.selectors.title', $option['title']);
        $this->assertFalse($option['switch']);
        $this->assertSame(
            [
                ['description' => 'paymentmethods.scalapay.widget.selectors.description'],
                ['type' => 'text', 'datas' => ['text' => 'paymentmethods.scalapay.widget.selectors.product', 'class' => '']],
                ['type' => 'input', 'datas' => [
                    'name' => 'scalapay_amount_selectors_product',
                    'type' => 'text',
                    'value' => '#price, .price',
                    'placeholder' => '.current-price-value',
                    'class' => '',
                ]],
                ['type' => 'text', 'datas' => ['text' => 'paymentmethods.scalapay.widget.selectors.cart', 'class' => '']],
                ['type' => 'input', 'datas' => [
                    'name' => 'scalapay_amount_selectors_cart',
                    'type' => 'text',
                    'value' => '',
                    'placeholder' => '.cart-summary-totals .cart-total .value',
                    'class' => '',
                ]],
                ['type' => 'text', 'datas' => ['text' => 'paymentmethods.scalapay.widget.selectors.checkout', 'class' => '']],
                ['type' => 'input', 'datas' => [
                    'name' => 'scalapay_amount_selectors_checkout',
                    'type' => 'text',
                    'value' => '',
                    'placeholder' => '.cart-summary-totals .cart-total .value',
                    'class' => '',
                ]],
            ],
            $option['descriptions']
        );
    }

    public function testTheSelectorFieldsAreEmptyWhenNothingIsStored()
    {
        $this->widget_action->shouldNotReceive('getAmountSelectors');

        $descriptions = $this->getAdvancedOptionsByName([])['scalapay_amount_selectors']['descriptions'];

        foreach ([2 => '.current-price-value', 4 => '.cart-summary-totals .cart-total .value', 6 => '.cart-summary-totals .cart-total .value'] as $index => $default) {
            $this->assertSame('', $descriptions[$index]['datas']['value']);
            $this->assertSame($default, $descriptions[$index]['datas']['placeholder']);
        }
    }

    public function testTheSelectorsOptionIsLeftOutWhenTheWidgetActionIsMissing()
    {
        $this->module->shouldReceive('getService')->with('payplug.action.scalapay_widget')->andReturn(null);

        $options = $this->getAdvancedOptionsByName([]);

        $this->assertArrayHasKey('scalapay_cart_widget', $options);
        $this->assertArrayNotHasKey('scalapay_amount_selectors', $options);
    }

    public function testTheMerchantTokenIsNeverSentToTheBackOffice()
    {
        $this->configuration->shouldReceive('getValue')->with('scalapay_merchant_token')->andReturn('tok_secret_42');

        $option = $this->class->getOption(['scalapay_merchant_token' => 'tok_secret_42']);

        $this->assertStringNotContainsString('tok_secret_42', json_encode($option));
    }

    /**
     * @param array $current_configuration
     *
     * @return array advanced options by name
     */
    private function getAdvancedOptionsByName($current_configuration)
    {
        $options = [];
        foreach ($this->class->getOption($current_configuration)['advanced_options'] as $option) {
            $options[$option['name']] = $option;
        }

        return $options;
    }
}
