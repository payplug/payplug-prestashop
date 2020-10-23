<?php

// Flippe pas Cyril, c'est pour éviter les erreurs PHP des classes qui l'utilisent!

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\DatabaseInterface;
use Db;

class DatabaseSpecific implements DatabaseInterface
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Db::getInstance();
        }
        catch (\Exception $e){
            var_dump($e);
        }
    }

    public function query($action, $command = null)
    {
        try {
            return $this->db->$action($command);
        } catch (\Exception $e){
            var_dump($e);
        }
    }
}