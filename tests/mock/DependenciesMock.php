<?php

namespace PayPlug\tests\mock;

class DependenciesMock
{
    public static function get()
    {
        return true;
    }

    public function l($string = false, $name = false)
    {
        return $string;
    }
}
