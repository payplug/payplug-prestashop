<?php

namespace PayPlug\tests\mock;

class CarrierMock
{
    public static function factory()
    {
        return new self();
    }

    public static function get()
    {
        $carrier = new \stdClass();
        $carrier->id = 1;
        $carrier->name = 'Carrier name';
        $carrier->date_add = '2021-01-01 00:00:00';
        $carrier->date_upd = '2021-01-01 00:00:00';
        $carrier->delay = 'fast or not';

        return $carrier;
    }
}
