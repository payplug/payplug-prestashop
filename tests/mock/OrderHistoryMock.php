<?php

namespace PayPlug\tests\mock;

class OrderHistoryMock
{
    public static function get()
    {
        $order_history = new \stdClass();

        $order_history->id = 1;
        $order_history->id_order = 2;
        $order_history->id_order_state = 3;

        return $order_history;
    }
}
