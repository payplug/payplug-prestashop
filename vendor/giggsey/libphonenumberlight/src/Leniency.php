<?php

namespace libphonenumberlight;

use libphonenumberlight\Leniency\Possible;
use libphonenumberlight\Leniency\StrictGrouping;
use libphonenumberlight\Leniency\Valid;
use libphonenumberlight\Leniency\ExactGrouping;

class Leniency
{
    public static function POSSIBLE()
    {
        return new Possible;
    }

    public static function VALID()
    {
        return new Valid;
    }

    public static function STRICT_GROUPING()
    {
        return new StrictGrouping;
    }

    public static function EXACT_GROUPING()
    {
        return new ExactGrouping;
    }
}
