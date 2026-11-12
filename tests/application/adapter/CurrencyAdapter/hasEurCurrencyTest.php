<?php

namespace PayPlug\tests\application\adapter\CurrencyAdapter;

/**
 * @group unit
 * @group adapter
 * @group currency_adapter
 */
class hasEurCurrencyTest extends BaseCurrencyAdapter
{
    /**
     * @dataProvider currenciesProvider
     *
     * @param bool $has_eur
     * @param bool $has_other
     */
    public function testReturnsWhetherEurIsAnActiveCurrency(array $currencies, $has_eur, $has_other)
    {
        $this->mockActiveCurrencies($currencies);

        $this->assertSame($has_eur, $this->adapter->hasEurCurrency());
    }

    public function testCurrenciesAreOnlyFetchedOnce()
    {
        $this->mockActiveCurrencies([['iso_code' => 'USD']]);

        $this->assertFalse($this->adapter->hasEurCurrency());
        $this->assertFalse($this->adapter->hasEurCurrency());
    }
}
