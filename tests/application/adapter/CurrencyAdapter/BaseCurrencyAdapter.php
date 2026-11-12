<?php

namespace PayPlug\tests\application\adapter\CurrencyAdapter;

use PayPlug\src\application\adapter\CurrencyAdapter;
use PHPUnit\Framework\TestCase;

abstract class BaseCurrencyAdapter extends TestCase
{
    protected $adapter;

    public function setUp(): void
    {
        $this->adapter = \Mockery::mock(CurrencyAdapter::class)->makePartial();
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function currenciesProvider()
    {
        yield 'no active currency' => [[], false, false];

        yield 'EUR only' => [[['iso_code' => 'EUR']], true, false];

        yield 'USD only' => [[['iso_code' => 'USD']], false, true];

        yield 'EUR and GBP' => [[['iso_code' => 'GBP'], ['iso_code' => 'EUR']], true, true];
    }

    protected function mockActiveCurrencies(array $currencies)
    {
        $this->adapter->shouldReceive('findAll')
            ->once()
            ->andReturn($currencies);
    }
}
