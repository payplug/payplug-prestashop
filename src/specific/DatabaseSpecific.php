<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\DatabaseInterface;
use Db;

class DatabaseSpecific implements DatabaseInterface
{
    public function __construct()
    {
        try {
            $db = Db::getInstance();
            $res = $db->executeS('SELECT * FROM '._DB_PREFIX_.'configuration');
            var_dump($res);
            exit;
        }
        catch (\Exception $e){
            var_dump($e);
        }

        
    }
}