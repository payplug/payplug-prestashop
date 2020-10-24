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
        try {
                $action = 'executeS';
                return $this->db->$action($SQLRequest);

        } catch (\Exception $e){
            var_dump($e);
        }
    }

    public function select($table, $data, $limit)
    {
        $req = 'SELECT ' . $data . ' FROM `' . $table . '`';
        return Db::getInstance()->executeS($req);
    }
}