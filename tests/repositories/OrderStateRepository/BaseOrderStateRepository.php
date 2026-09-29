<?php

namespace PayPlug\tests\repositories\OrderStateRepository;

use PayPlug\src\repositories\OrderStateRepository;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

abstract class BaseOrderStateRepository extends TestCase
{
    use FormatDataProvider;

    protected $configuration;
    protected $dependencies;
    protected $order_state_adapter;
    protected $order_state_model_repository;
    protected $plugin;
    protected $repository;
    protected $validate;

    public function setUp(): void
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';

        $this->configuration = \Mockery::mock('ConfigurationClass');
        $this->order_state_adapter = \Mockery::mock('OrderStateAdapter');
        $this->validate = \Mockery::mock('ValidateAdapter');
        $this->order_state_model_repository = \Mockery::mock('OrderStateModelRepository');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getOrderStateRepository' => $this->order_state_model_repository,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $log = \Mockery::mock('MyLogPhp');
        $log->shouldReceive('info');

        $this->repository = new OrderStateRepository(
            $this->configuration,
            \Mockery::mock('ConstantAdapter'),
            $this->dependencies,
            \Mockery::mock('LanguageAdapter'),
            $this->order_state_adapter,
            \Mockery::mock('ToolsAdapter'),
            $this->validate,
            $log
        );
    }

    protected function getOrderState($id, $module_name)
    {
        $order_state = new \stdClass();
        $order_state->id = $id;
        $order_state->deleted = 0;
        $order_state->module_name = $module_name;

        return $order_state;
    }
}
