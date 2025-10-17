<?php

namespace PayPlug\tests\actions\CartAction;

use PayPlug\src\actions\CartAction;
use PayPlug\src\models\classes\Configuration;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseCartAction extends TestCase
{
    protected $action;
    protected $cartAdapter;
    protected $configClass;
    protected $configuration;
    protected $context;
    protected $context_adapter;
    protected $customer_adapter;
    protected $dependencies;
    protected $dispatcher;
    protected $instance;
    protected $plugin;
    protected $tools_adapter;

    public function setUp()
    {
        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration->shouldReceive([
            'get' => true,
        ]);

        $this->plugin = \Mockery::mock('Plugin');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->configClass = \Mockery::mock('ConfigClass');
        $this->dependencies->configClass = $this->configClass;

        $this->context_adapter = \Mockery::mock('Context');
        $this->context = ContextMock::get();
        $this->context->cart = \Mockery::mock('Cart');
        $this->context->cart->id = 1;

        $this->customer_adapter = \Mockery::mock('Customer');

        $this->context_adapter->shouldReceive([
            'get' => $this->context,
        ]);
        $this->dispatcher = \Mockery::mock('Dispatcher');
        $this->instance = \Mockery::mock('Instance');
        $this->controller = \Mockery::mock('Controller');

        $this->tools_adapter = \Mockery::mock('ToolsAdapter');

        $this->dispatcher->shouldReceive([
            'getInstance' => $this->instance,
            'getController' => $this->controller,
        ]);
        $this->plugin->shouldReceive([
            'getCart' => $this->cartAdapter,
            'getContext' => $this->context_adapter,
            'getCustomer' => $this->customer_adapter,
            'getConfigurationClass' => $this->configuration,
            'getDispatcher' => $this->dispatcher,
            'getTools' => $this->tools_adapter,
        ]);

        $this->dependencies->name = 'payplug';

        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->action = \Mockery::mock(CartAction::class, [$this->dependencies])->makePartial();
    }
}
