<?php

namespace PayPlug\tests\models\classes\paymentMethod\ScalapayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\ScalapayPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

class BaseScalapayPaymentMethod extends BasePaymentMethod
{
    protected $widget_action;

    public function setUp(): void
    {
        parent::setUp();

        $this->widget_action = \Mockery::mock('ScalapayWidgetAction');
        $this->widget_action->shouldReceive('getStoredAmountSelectors')->andReturn([])->byDefault();
        $this->widget_action->shouldReceive('getThemeAmountSelectors')->andReturnUsing(function ($page) {
            return 'product' === $page ? ['.current-price-value'] : ['.cart-summary-totals .cart-total .value'];
        })->byDefault();
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.action.scalapay_widget')
            ->andReturn($this->widget_action)
            ->byDefault();

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
