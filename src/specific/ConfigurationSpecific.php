<?php


namespace PayPlug\src\specific;


use PayPlug\src\interfaces\ConfigurationInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;

class ConfigurationSpecific implements ConfigurationInterface
{
    function get($configuration_name)
    {
        return Configuration::get('PAYPLUG_' . strtoupper($configuration_name));
    }
}