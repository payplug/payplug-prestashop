<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\ConfigurationInterface;
use \Configuration;

class ConfigurationSpecific implements ConfigurationInterface
{
    private $psConfiguration;

    public function __construct()
    {
        $this->psConfiguration = new Configuration();
    }

    public function get($configuration_name)
    {
        return $this->psConfiguration::get($configuration_name);
    }

    public function updateValue($key, $value)
    {
        return $this->psConfiguration::updateValue($key, $value);
    }

    public function deleteByName($key)
    {
        return $this->psConfiguration::deleteByName($key);
    }
}