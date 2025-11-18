<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
final class getOneyPriceLimitTest extends BaseOneyPaymentMethod
{
    public $error_limits;

    public function setUp()
    {
        parent::setUp();
        $this->configuration->shouldReceive('getValue')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('42');
        $this->configuration->shouldReceive('getValue')
            ->with('oney_custom_min_amounts')
            ->andReturn('EUR:42000');
        $this->configuration->shouldReceive('getValue')
            ->with('oney_custom_max_amounts')
            ->andReturn('EUR:420000');
        $this->currency_adapter->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);
        $this->error_limits = [
            'min' => false,
            'max' => false,
        ];
        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($action, $value, $params2 = false) {
                return strtoupper($value);
            });
    }

    public function testWhenDefaultCurrencyIsNotValid()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertSame(
            $this->error_limits,
            $this->class->getOneyPriceLimit()
        );
    }

    public function testWhenLimitsIsCustom()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->assertSame(
            [
                'min' => 42000,
                'max' => 420000,
            ],
            $this->class->getOneyPriceLimit(true)
        );
    }

    public function testWhenLimitsIsNotCustom()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->assertSame(
            [
                'min' => 10000,
                'max' => 300000,
            ],
            $this->class->getOneyPriceLimit(false)
        );
    }
}
