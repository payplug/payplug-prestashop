<?php

namespace PayPlug\src\interfaces;

interface ConfigurationInterface
{
    function get($configuration_name);
    function updateValue($key, $value);
    function deleteByName($key);
}