<?php

namespace PayPlug\src\interfaces;

interface CountryInterface
{
    function getByIso($id_currency);
}