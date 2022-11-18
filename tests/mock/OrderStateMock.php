<?php

namespace PayPlug\tests\mock;

class OrderStateMock
{
    public static function get()
    {
        $order_state = new \stdClass();

        $order_state->id = 42;
        $order_state->deleted = 0;

        return $order_state;
    }
}
