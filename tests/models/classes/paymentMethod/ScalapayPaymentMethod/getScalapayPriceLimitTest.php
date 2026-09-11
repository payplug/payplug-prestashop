<?php

namespace PayPlug\tests\models\classes\paymentMethod\ScalapayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group scalapay_payment_method_class
 */
class getScalapayPriceLimitTest extends BaseScalapayPaymentMethod
{
    public function testWhenNoCustomLimitIsConfigured()
    {
        $this->stubCustomLimits('', '');

        $this->assertSame(
            [
                'min' => 500,
                'max' => 400000,
            ],
            $this->class->getScalapayPriceLimit()
        );
    }

    public function testWhenCustomLimitsNarrowTheAccountRange()
    {
        $this->stubCustomLimits('EUR:1000', 'EUR:200000');

        $this->assertSame(
            [
                'min' => 1000,
                'max' => 200000,
            ],
            $this->class->getScalapayPriceLimit()
        );
    }

    public function testWhenCustomLimitsWidenTheAccountRange()
    {
        $this->stubCustomLimits('EUR:100', 'EUR:900000');

        $this->assertSame(
            [
                'min' => 500,
                'max' => 400000,
            ],
            $this->class->getScalapayPriceLimit()
        );
    }

    public function testWhenOnlyTheAccountRangeIsRequested()
    {
        $this->stubCustomLimits('EUR:1000', 'EUR:200000');

        $this->assertSame(
            [
                'min' => 500,
                'max' => 400000,
            ],
            $this->class->getScalapayPriceLimit(false)
        );
    }
}
