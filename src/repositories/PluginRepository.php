<?php


namespace PayPlug\src\repositories;


use PayPlug\classes\MyLogPHP;
use PayPlug\src\entities\PluginEntity;

class PluginRepository extends Repository
{
    public function __construct()
    {
        //$logger = new MyLogPHP(_PS_MODULE_DIR_ . $this->name . '/log/general-log.csv');

        $this->entity = new PluginEntity();
        $this->entity
            ->setApiVersion('2019-08-06');
            //->setLogger($logger);
    }
}