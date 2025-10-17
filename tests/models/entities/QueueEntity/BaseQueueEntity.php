<?php

namespace PayPlug\tests\models\entities\QueueEntity;

use PayPlug\src\models\entities\QueueEntity;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseQueueEntity extends TestCase
{
    use FormatDataProvider;

    protected $id;
    protected $entity;
    protected $date;
    protected $cart_hash;
    protected $definition;
    protected $id_cart;
    protected $method;
    protected $resource_id;
    protected $type;

    public function setUp()
    {
        parent::setUp();
        $this->entity = \Mockery::mock(QueueEntity::class)->makePartial();
        $this->id = 42;
        $this->id_cart = 42;
        $this->date = '2021-12-31 23:59:42';
        $this->definition = [
            'table' => 'payplug_queue',
            'primary' => 'id_payplug_queue',
            'fields' => [
                'date_add' => ['type' => 'string'],
                'date_upd' => ['type' => 'string'],
                'id_cart' => ['type' => 'integer', 'required' => true],
                'resource_id' => ['type' => 'string', 'required' => true],
                'treated' => ['type' => 'boolean'],
                'type' => ['type' => 'string'],
            ],
        ];
        $this->resource_id = 'pay_1234azer';
        $this->type = 'type';
        $this->treated = false;
    }
}
