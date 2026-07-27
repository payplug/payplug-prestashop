<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getThresholdsTest extends BaseOneyPaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenDataHasInvalidFormat($current_configuration)
    {
        $this->assertSame(
            [],
            $this->class->getThresholds($current_configuration)
        );
    }

    public function testWhenThresholdsIsReturn()
    {
        $current_configuration = [
            'oney_min_amounts' => 'EUR:10000',
            'oney_max_amounts' => 'EUR:300000',
            'oney_custom_min_amounts' => 'EUR:10000',
            'oney_custom_max_amounts' => 'EUR:300000',
        ];
        $min_amount = 100;
        $max_amount = 3000;
        $expected = [
            'name' => 'thresholds',
            'image_url' => 'oney/payplug-thresholds.jpg',
            'title' => 'thresholds.title',
            'descriptions' => [
                'description' => 'thresholds.description',
                'min_amount' => [
                    'name' => 'oney_min_amounts',
                    'value' => (float) $min_amount,
                    'placeholder' => (float) $min_amount,
                    'default' => (float) $min_amount,
                ],
                'inter' => 'thresholds.inter',
                'max_amount' => [
                    'name' => 'oney_max_amounts',
                    'value' => (float) $max_amount,
                    'placeholder' => (float) $max_amount,
                    'default' => (float) $max_amount,
                ],
                'error' => [
                    'text' => 'thresholds.error.text',
                    'maxtext' => 'thresholds.error.max.text',
                    'mintext' => 'thresholds.error.min.text',
                ],
            ],
            'switch' => false,
        ];
        $this->assertSame(
            $expected,
            $this->class->getThresholds($current_configuration)
        );
    }
}
