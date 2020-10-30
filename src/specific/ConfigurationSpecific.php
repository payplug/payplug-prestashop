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
        // Old PHP configs can't accept $this->classVar::staticMethod()
        // But only $var::staticMethod()
        $config = $this->psConfiguration;
        return $config::get($configuration_name);
    }

    public function updateValue($key, $value)
    {
        $config = $this->psConfiguration;
        return $config::updateValue($key, $value);
    }

    public function deleteByName($key)
    {
        $config = $this->psConfiguration;
        return $config::deleteByName($key);
    }
}