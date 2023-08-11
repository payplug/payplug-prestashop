<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\src\actions\PaymentAction;
use PayPlug\src\models\classes\Configuration;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BasePaymentAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $cartAdapter;
    public $configClass;
    public $configuration;
    public $context;
    public $dependencies;
    public $plugin;
    public $toolsAdapter;

    protected function setUp()
    {
        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration
            ->shouldReceive([
                'get' => true,
            ]);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": true, "amex": true}');

        $this->cartAdapter = \Mockery::mock('CartAdapter');
        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');

        $this->plugin = \Mockery::mock('Plugin');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->configClass = \Mockery::mock('ConfigClass');
        $this->dependencies->configClass = $this->configClass;

        $this->context = \Mockery::mock('Context');
        $this->context
            ->shouldReceive([
            'get' => ContextMock::get(),
        ]);

        $this->plugin
            ->shouldReceive([
                'getCart' => $this->cartAdapter,
                'getContext' => $this->context,
                'getTools' => $this->toolsAdapter,
                'getConfigurationClass' => $this->configuration,
            ]);

        $this->dependencies->name = 'payplug';

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);

        $this->action = \Mockery::mock(PaymentAction::class, [$this->dependencies])->makePartial();
    }
}
