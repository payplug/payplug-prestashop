<?php

namespace PayPlug\tests\models\classes\paymentMethod\ScalapayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\ScalapayPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseScalapayPaymentMethod extends BasePaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $this->class = \Mockery::mock(ScalapayPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    /**
     * @description Stub the merchant's own Scalapay limits. An empty string means the
     *              merchant never customised them.
     *
     * @param string $min
     * @param string $max
     */
    protected function stubCustomLimits($min = '', $max = '')
    {
        $this->configuration->shouldReceive('getValue')
            ->with('scalapay_custom_min_amounts')
            ->andReturn($min);
        $this->configuration->shouldReceive('getValue')
            ->with('scalapay_custom_max_amounts')
            ->andReturn($max);
    }
}
