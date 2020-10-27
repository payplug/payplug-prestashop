<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\ToolsInterface;
use Tools;

class ToolsSpecific implements ToolsInterface
{

    function tool($action, $param1, $param2 = null)
    {
//        var_dump($action,$param1,$param2); exit;
        
        return Tools::$action($param1, $param2);
    }
}