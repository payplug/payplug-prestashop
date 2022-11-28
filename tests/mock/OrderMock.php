<?php

namespace PayPlug\tests\mock;

class OrderMock
{
    public static function get()
    {
        $order = new \stdClass();

        $order->id = 42;
        $order->id_cart = 42;

        return $order;
    }
}
