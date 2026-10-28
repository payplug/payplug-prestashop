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
     * @description Stub the `scalapay_custom_*` configuration keys.
     *
     * @param string $min
     * @param string $max
     */
    protected function stubLegacyCustomLimits($min = '', $max = '')
    {
        $this->configuration->shouldReceive('getValue')
            ->with('scalapay_custom_min_amounts')
            ->andReturn($min);
        $this->configuration->shouldReceive('getValue')
            ->with('scalapay_custom_max_amounts')
            ->andReturn($max);
    }
}
