<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class isSupportedCurrencyTest extends BasePaymentMethod
{
    public function testCartCurrencyAmongTheMerchantCurrenciesIsSupported()
    {
        $this->context->currency->iso_code = 'GBP';
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR;GBP');

        $this->assertTrue($this->class->isSupportedCurrency());
    }

    public function testCartCurrencyOutsideTheMerchantCurrenciesIsNotSupported()
    {
        $this->context->currency->iso_code = 'USD';

        $this->assertFalse($this->class->isSupportedCurrency());
    }

    public function testCurrencyIsoCodeIsMatchedExactly()
    {
        $this->context->currency->iso_code = 'eur';

        $this->assertFalse($this->class->isSupportedCurrency());
    }

    public function testCartWithoutCurrencyIsNotSupported()
    {
        $this->context->currency = null;

        $this->assertFalse($this->class->isSupportedCurrency());
    }

    public function testCartCurrencyWithoutIsoCodeIsNotSupported()
    {
        $this->context->currency->iso_code = null;

        $this->assertFalse($this->class->isSupportedCurrency());
    }

    /**
     * @dataProvider emptyCurrenciesProvider
     *
     * @param mixed $currencies
     */
    public function testNoCurrencyIsSupportedWhenTheMerchantCurrenciesAreEmpty($currencies)
    {
        $this->context->currency->iso_code = 'EUR';
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn($currencies);

        $this->assertFalse($this->class->isSupportedCurrency());
    }

    public function emptyCurrenciesProvider()
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }
}
