<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\ToolsInterface;
use Tools;

class ToolsSpecific implements ToolsInterface
{

    function tool($action, $param)
    {
        Tools::$action($param);
    }
}