<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getOneyLimitsCurrencyTest extends BaseOneyPaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $this->configuration->shouldReceive('getValue')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('42');
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $value) {
                return strtoupper($value);
            });
    }

    public function testShopDefaultCurrencyIsReturnedUppercase()
    {
        $currency = new \stdClass();
        $currency->iso_code = 'jpy';
        $this->currency_adapter->shouldReceive('get')
            ->with(42)
            ->andReturn($currency);
        $this->validate_adapter->shouldReceive('validate')->andReturn(true);

        $this->assertSame('JPY', $this->class->getOneyLimitsCurrency());
    }

    public function testUnloadedShopDefaultCurrencyReturnsEmptyString()
    {
        $this->currency_adapter->shouldReceive('get')->andReturn(new \stdClass());
        $this->validate_adapter->shouldReceive('validate')->andReturn(false);

        $this->assertSame('', $this->class->getOneyLimitsCurrency());
    }
}
