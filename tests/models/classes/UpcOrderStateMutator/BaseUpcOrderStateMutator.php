<?php

namespace PayPlug\tests\models\classes\UpcOrderStateMutator;

use PayPlug\src\models\classes\UpcOrderStateMutator;
use PHPUnit\Framework\TestCase;

abstract class BaseUpcOrderStateMutator extends TestCase
{
    protected $configuration_class;
    protected $dependencies;
    protected $logger_repository;
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
        $this->logger_repository = \Mockery::mock('LoggerRepository');
        $this->order = \Mockery::mock('Order');
        $this->order->id = 12;

        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getOrder')->andReturn($this->order_adapter);
        $this->plugin->shouldReceive('getOrderClass')->andReturn($this->order_class);
        $this->plugin->shouldReceive('getConfigurationClass')->andReturn($this->configuration_class);
        $this->plugin->shouldReceive('getLogger')->andReturn($this->logger_repository);
        $this->order_adapter->shouldReceive('get')->with(12)->andReturn($this->order);

        $this->mutator = new UpcOrderStateMutator($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * @param bool $sandbox
     * @param int $current_state
     */
    protected function mockContext($sandbox, $current_state)
    {
        $suffix = $sandbox ? '_test' : '';
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn($sandbox);
        $this->configuration_class->shouldReceive('getValue')->with('order_state_partial_refund' . $suffix)->andReturn(40);
        $this->order_class->shouldReceive('getOrderStates')->andReturn([
            'paid' => 20,
            'pending' => 30,
            'refund' => 50,
            'error' => 60,
        ]);
        $this->order->shouldReceive('getCurrentState')->andReturn($current_state);
    }
}
