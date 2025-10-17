<?php

namespace PayPlug\tests\mock;

class CustomerMock
{
    public static function get()
    {
        $customer = new \stdClass();
        $customer->id = 1;
        $customer->lastname = 'Lorem';
        $customer->firstname = 'Ipsum';
        $customer->email = 'customer@payplug.com';
        $customer->date_add = '2020-12-21 12:03:52';
        $customer->date_upd = '2021-01-11 11:24:31';

        return $customer;
    }
}
