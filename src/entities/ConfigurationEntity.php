<?php


namespace PayPlug\src\entities;



use PayPlug\src\specific\ConfigurationSpecific;


class ConfigurationEntity
{
    private $specific_class;

    public function __construct()
    {
        $this->specific_class = new ConfigurationSpecific();
    }

    public function get($configuration_name)
    {
        return $this->specific_class->get($configuration_name);
    }
}