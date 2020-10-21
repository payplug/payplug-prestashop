<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\ConfigurationInterface;
use Configuration;

class ConfigurationSpecific implements ConfigurationInterface
{
    public function get($configuration_name)
    {
        $psConfiguration = new Configuration();
        return $psConfiguration::get($configuration_name);
    }
}