<?php


namespace PayPlug\src\specific;


use PayPlug\src\interfaces\PayPlugConfigurationInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;

class SpecificConfiguration implements PayPlugConfigurationInterface
{

    function get($configuration_name)
    {
        return Configuration::get('PAYPLUG_' . strtoupper($configuration_name));
    }
}