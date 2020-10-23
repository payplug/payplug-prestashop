<?php

namespace PayPlug\src\interfaces;

interface QueryInterface
{
    public function query($SQLRequest);
    public function getLastId();
}