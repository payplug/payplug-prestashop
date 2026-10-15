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
        $this->stubCustomLimits('', '');

        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }

    public function testWhenNoCustomLimitIsConfiguredThresholdsFallBackOnTheAccountRange()
    {
        $this->stubCustomLimits('', '');

        $this->assertSame(
            [
                [
                    'name' => 'thresholds',
                    'image_url' => 'modules/payplug/views/img/oney/payplug-thresholds.jpg',
                    'title' => 'paymentmethods.scalapay.thresholds.title',
                    'descriptions' => [
                        'description' => 'paymentmethods.scalapay.thresholds.description',
                        'min_amount' => [
                            'name' => 'scalapay_min_amounts',
                            'value' => 5.0,
                            'placeholder' => 5.0,
                            'default' => 5.0,
                        ],
                        'inter' => 'paymentmethods.scalapay.thresholds.inter',
                        'max_amount' => [
                            'name' => 'scalapay_max_amounts',
                            'value' => 4000.0,
                            'placeholder' => 4000.0,
                            'default' => 4000.0,
                        ],
                        'error' => [
                            'text' => 'paymentmethods.scalapay.thresholds.error.text',
                            'maxtext' => 'paymentmethods.scalapay.thresholds.error.max.text',
                            'mintext' => 'paymentmethods.scalapay.thresholds.error.min.text',
                        ],
                    ],
                    'switch' => false,
                ],
            ],
            $this->class->getOption([])['advanced_options']
        );
    }

    public function testWhenCustomLimitsAreConfiguredTheyAreDisplayedWithinTheAccountRange()
    {
        $this->stubCustomLimits('EUR:1000', 'EUR:200000');

        $descriptions = $this->class->getOption([])['advanced_options'][0]['descriptions'];

        $this->assertSame(10.0, $descriptions['min_amount']['value']);
        $this->assertSame(5.0, $descriptions['min_amount']['default']);
        $this->assertSame(2000.0, $descriptions['max_amount']['value']);
        $this->assertSame(4000.0, $descriptions['max_amount']['default']);
    }
}
