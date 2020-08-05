<?php


namespace PayPlug\src\repositories;


class Repository
{
    private $entity;

    public function setEntity($entity)
    {
        $this->entity = $entity;
        return  $this;
    }
    public function getEntity()
    {
        return $this->entity;
    }
}