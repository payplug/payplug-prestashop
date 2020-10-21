<?php

namespace PayPlug\src\interfaces;

interface DatabaseInterface
{
    public function query($action,$command);
}