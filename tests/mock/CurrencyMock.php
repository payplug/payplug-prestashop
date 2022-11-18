<?php

namespace PayPlug\tests\mock;

class CurrencyMock
{
    public static function get()
    {
        $currency = new \stdClass();
        $currency->id = 1;
        $currency->iso_code = 'EUR';

        return $currency;
    }
}
