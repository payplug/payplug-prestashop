<?php

namespace PayPlug\tests\mock;

class LinkMock
{
    public static function get()
    {
        $link = new \stdClass();
        $link->url = 'www.test.fr';

        return $link;
    }
}
