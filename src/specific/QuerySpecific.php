<?php

namespace PayPlug\src\specific;

use PayPlug\src\interfaces\QueryInterface;
use Db;

class QuerySpecific implements QueryInterface
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

    public function query($SQLRequest)
    {
        var_dump($SQLRequest);
        
//        if (stripos($SQLRequest,'UPDATE') !== false) {
//            var_dump($SQLRequest); exit;
//
//        }
        try {
            $action = 'execute';

            if (stripos($SQLRequest,'SELECT') !== false) {
                $action = 'executeS';
            }
            return $this->db->$action($SQLRequest);

        } catch (\Exception $e){
            var_dump($e);
        }
    }

    public function getLastId()
    {
        return $this->db->Insert_ID();
    }

}