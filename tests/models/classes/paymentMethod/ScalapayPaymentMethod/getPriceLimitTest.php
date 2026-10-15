<?php

namespace PayPlug\tests\models\classes\paymentMethod\ScalapayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group scalapay_payment_method_class
 */
class getPriceLimitTest extends BaseScalapayPaymentMethod
{
    public function testTheApiRangeIsReturned()
    {
        $this->stubLegacyCustomLimits('', '');

        $this->assertSame(
            [
                'min' => 'EUR:500',
                'max' => 'EUR:400000',
            ],
            $this->class->getPriceLimit()
        );
    }

    public function testLegacyCustomLimitsDoNotNarrowTheApiRange()
    {
        $this->stubLegacyCustomLimits('EUR:1000', 'EUR:200000');

        $this->assertSame(
            [
                'min' => 'EUR:500',
                'max' => 'EUR:400000',
            ],
            $this->class->getPriceLimit()
        );
    }
}
