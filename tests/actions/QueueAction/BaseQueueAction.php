<?php

namespace PayPlug\tests\actions\QueueAction;

use PayPlug\src\actions\QueueAction;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseQueueAction extends TestCase
{
    use FormatDataProvider;

    public $dependencies;
    public $logger;
    public $plugin;
    public $repository;

    public $id_cart;
    public $resource_id;
    public $type;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->plugin = \Mockery::mock('Plugin');

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);
        $this->repository = \Mockery::mock('QueueRepository');

        $this->plugin->shouldReceive([
            'getLogger' => $this->logger,
            'getQueueRepository' => $this->repository,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);
        $this->action = \Mockery::mock(QueueAction::class, [$this->dependencies])->makePartial();

        $this->id_cart = 42;
        $this->resource_id = 'pay_12345azerty';
        $this->type = 'payment';
        $this->queue = [
            'id_payplug_queue' => 42,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
            'id_cart' => $this->id_cart,
            'resource_id' => $this->resource_id,
            'treated' => false,
            'type' => $this->type,
        ];
    }
}
