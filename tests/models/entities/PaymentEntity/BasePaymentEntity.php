<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\models\entities\PaymentEntity;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BasePaymentEntity extends TestCase
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
    protected $schedules;

    public function setUp()
    {
        parent::setUp();
        $this->entity = \Mockery::mock(PaymentEntity::class)->makePartial();
        $this->id = 42;
        $this->id_cart = 42;
        $this->date = '2021-12-31 23:59:42';
        $this->cart_hash = 'cart_hash';
        $this->method = 'payment_method';
        $this->definition = [
            'table' => 'payplug_payment',
            'primary' => 'id_payplug_payment',
            'fields' => [
                'resource_id' => ['type' => 'string', 'required' => true],
                'method' => ['type' => 'string', 'required' => true],
                'id_cart' => ['type' => 'integer', 'required' => true],
                'cart_hash' => ['type' => 'string', 'required' => true],
                'schedules' => ['type' => 'string'],
                'date_upd' => ['type' => 'string'],
            ],
        ];
        $this->resource_id = 'pay_1234azer';
        $this->schedules = 'payment_schedules';
    }
}
