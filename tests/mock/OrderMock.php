<?php

namespace PayPlug\tests\mock;

class OrderMock
{
    public static function get()
    {
        $order = new \stdClass();

        $order->id = 42;
        $order->id_cart = 42;
        $order->current_state = 42;
        $order->module = 'payplug';

        return $order;
    }
}
