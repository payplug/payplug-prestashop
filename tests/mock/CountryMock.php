<?php

namespace PayPlug\tests\mock;

class CountryMock
{
    public static function get()
    {
        $country = new \stdClass();

        $country->id = 1;
        $country->id_zone = 1;
        $country->id_currency = 1;
        $country->iso_code = 'FR';
        $country->call_prefix = '33';
        $country->name = [
            1 => 'France',
            2 => 'France',
            3 => 'France',
            4 => 'France',
        ];
        $country->active = 1;
        $country->id_lang = null;

        return $country;
    }
}
