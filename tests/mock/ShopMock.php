<?php

namespace PayPlug\tests\mock;

class ShopMock
{
    public static function get()
    {
        $shop = new \stdClass();

        $shop->id = 1;
        $shop->domain_ssl = 'my-mock.com';

        return $shop;
    }
}
