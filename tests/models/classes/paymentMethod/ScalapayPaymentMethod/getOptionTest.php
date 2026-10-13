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
            $this->class->getOption([])['advanced_options']
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

    public function testCountriesFieldIsAddedAlongsideTheAmountFields()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{"scalapay":["IT"]}');

        $option = $this->class->getOption([]);

        $this->assertCount(1, $option['advanced_options']);
        $this->assertSame('scalapay_min_amounts', $option['advanced_options'][0]['descriptions']['min_amount']['name']);
        $this->assertSame('scalapay_countries', $option['countries']['name']);
        $this->assertSame(['IT'], $option['countries']['value']);
    }
}
