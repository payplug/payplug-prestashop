<?php

namespace PayPlug\tests\mock;

class LanguageMock
{
    public static function get()
    {
        $language = new \stdClass();
        $language->id = 1;
        $language->iso_code = 'fr';

        return $language;
    }
}
