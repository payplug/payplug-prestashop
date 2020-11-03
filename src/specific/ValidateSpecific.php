<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\ValidateInterface;
use \Validate;

class ValidateSpecific implements ValidateInterface
{
    public function validate($action, $object)
    {
        return Validate::$action($object);
    }

}