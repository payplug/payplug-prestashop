<?php

namespace PayPlug\src\repositories;

use PayPlug\src\specific\QuerySpecific;

class QueryRepository extends Repository
{
    private $specific_class;

    public function __construct()
    {
        $this->specific_class = new QuerySpecific();
    }

    public function select($table, $data, $limit)
    {
        $res = $this->specific_class->select($table, $data, $limit);
        return $res;
    }
}
