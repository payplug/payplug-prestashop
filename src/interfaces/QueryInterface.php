<?php

namespace PayPlug\src\interfaces;

interface QueryInterface
{
    public function query($action, $command);
    public function select($table, $data, $limit);
}