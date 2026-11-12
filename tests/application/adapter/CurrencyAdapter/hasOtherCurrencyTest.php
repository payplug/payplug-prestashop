<?php

namespace PayPlug\tests\application\adapter\CurrencyAdapter;

/**
 * @group unit
 * @group adapter
 * @group currency_adapter
 */
class hasOtherCurrencyTest extends BaseCurrencyAdapter
{
    /**
     * @dataProvider currenciesProvider
     *
     * @param bool $has_eur
     * @param bool $has_other
     */
    public function testReturnsWhetherANonEurCurrencyIsActive(array $currencies, $has_eur, $has_other)
    {
        $this->mockActiveCurrencies($currencies);

        $this->assertSame($has_other, $this->adapter->hasOtherCurrency());
    }

    public function testCurrenciesAreOnlyFetchedOnce()
    {
        $this->mockActiveCurrencies([['iso_code' => 'EUR']]);

        $this->assertFalse($this->adapter->hasOtherCurrency());
        $this->assertFalse($this->adapter->hasOtherCurrency());
    }
}
