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
    protected $dependencies;
    protected $dispatcher;
    protected $browser_validator;
    protected $plugin;
    protected $toolsAdapter;

    protected function setUp()
    {
        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration
            ->shouldReceive([
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

        $this->context_adapter
            ->shouldReceive([
                                'get' => $this->context,
                            ]);

        $this->plugin
            ->shouldReceive([
                                'getCart' => $this->cartAdapter,
                                'getContext' => $this->context_adapter,
                                'getConfigurationClass' => $this->configuration,
                            ]);

        $this->dependencies->name = 'payplug';

        $this->browser_validator = \Mockery::mock('BrowserValidator');
        $this->dependencies
            ->shouldReceive([
                                'getPlugin' => $this->plugin,
                                'getValidators' => [
                                    'browser' => $this->browser_validator,
                                ],
                            ]);

        $this->action = \Mockery::mock(CartAction::class, [$this->dependencies])->makePartial();
    }
}
