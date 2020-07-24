<?php


namespace PayPlug\entities;



use PayPlug\src\specific\SpecificConfiguration;


class PayPlugConfigurationEntity
{
    private $specific_class;

    public function __construct()
    {
        $this->specific_class = new SpecificConfiguration();
    }

    public function get($configuration_name)
    {
        return $this->specific_class->get($configuration_name);
    }
}