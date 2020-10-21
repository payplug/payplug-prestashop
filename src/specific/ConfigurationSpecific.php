<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\ConfigurationInterface;
use Configuration;

class ConfigurationSpecific implements ConfigurationInterface
{
    public function get($configuration_name)
    {
        return (new Configuration())::get($configuration_name);
    }
}