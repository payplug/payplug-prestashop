<?php

namespace PayPlug\tests\actions\OneyAction;

use PayPlug\src\actions\OneyAction;
use PayPlug\src\models\classes\Translation;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseOneyAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $configuration;
    public $configuration_class;
    public $context;
    public $context_adapter;
    public $dependencies;
    public $dispatcher;
    public $instance;
    public $plugin;
    public $toolsAdapter;
    public $translation;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->plugin = \Mockery::mock('Plugin');

        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');

        $this->configuration = \Mockery::mock(Configuration::class)->makePartial();
        $this->configuration_class = \Mockery::mock(ConfigurationClass::class)->makePartial();
        $this->context_adapter = \Mockery::mock('Context');
        $this->context_adapter->cart = \Mockery::mock('Cart');
        $this->context_adapter->cart
            ->shouldReceive([
                'getOrderTotal' => 42.42,
            ]);
        $this->context_adapter
            ->shouldReceive([
                'get' => ContextMock::get(),
            ]);

        $this->dispatcher = \Mockery::mock('Dispatcher');
        $this->instance = \Mockery::mock('Instance');
        $this->dispatcher
            ->shouldReceive([
                'getInstance' => $this->instance,
            ]);

        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $this->payment_method,
            ]);

        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $this->configuration,
                'getConfigurationClass' => $this->configuration_class,
                'getContext' => $this->context_adapter,
                'getDispatcher' => $this->dispatcher,
                'getPaymentMethodClass' => $this->payment_method_class,
                'getTools' => $this->toolsAdapter,
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation
            ->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });

        $this->action = \Mockery::mock(OneyAction::class, [$this->dependencies])->makePartial();
    }
}
