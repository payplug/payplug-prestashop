<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group oney_payment_method_classes
 *
 * @runTestsInSeparateProcesses
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
            $this->classe->getThresholds($current_configuration)
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
        $expected = [
            'name' => 'thresholds',
            'image_url' => 'oney/payplug-thresholds.jpg',
            'title' => 'thresholds.title',
            'descriptions' => [
                'description' => 'thresholds.description',
                'min_amount' => [
                    'name' => 'oney_min_amounts',
                    'value' => 100,
                    'placeholder' => 100,
                    'default' => 100,
                ],
                'inter' => 'thresholds.inter',
                'max_amount' => [
                    'name' => 'oney_max_amounts',
                    'value' => 3000,
                    'placeholder' => 3000,
                    'default' => 3000,
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
            $this->classe->getThresholds($current_configuration)
        );
    }
}
