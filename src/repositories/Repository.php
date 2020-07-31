<?php


namespace PayPlug\src\repositories;


class Repository
{
    public $entity;

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
}   