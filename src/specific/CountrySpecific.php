<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\CountryInterface;
use Country;

class CountrySpecific implements CountryInterface
{
    public function getByIso($id_currency)
    {
        return Country::getByIso($id_currency);
    }
}