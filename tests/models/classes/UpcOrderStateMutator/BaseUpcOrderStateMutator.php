<?php

namespace PayPlug\tests\models\classes\UpcOrderStateMutator;

use PayPlug\src\models\classes\UpcOrderStateMutator;
use PHPUnit\Framework\TestCase;

abstract class BaseUpcOrderStateMutator extends TestCase
{
    protected $configuration_class;
    protected $dependencies;
    protected $logger;
    protected $mutator;
    protected $order;
    protected $order_adapter;
    protected $order_class;
    protected $plugin;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->plugin = \Mockery::mock('Plugin');
        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->order_class = \Mockery::mock('OrderClass');
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->logger = \Mockery::mock('Logger');
        $this->order = \Mockery::mock('Order');
        $this->order->id = 42;

        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getOrder')->andReturn($this->order_adapter);
        $this->plugin->shouldReceive('getOrderClass')->andReturn($this->order_class);
        $this->plugin->shouldReceive('getConfigurationClass')->andReturn($this->configuration_class);
        $this->plugin->shouldReceive('getLogger')->andReturn($this->logger);

        $this->mutator = new UpcOrderStateMutator($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
