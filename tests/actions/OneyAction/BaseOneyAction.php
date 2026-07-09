<?php

namespace PayPlug\tests\actions\OneyAction;

use PayPlug\src\actions\OneyAction;
use PayPlug\src\models\classes\Translation;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

abstract class BaseOneyAction extends TestCase
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

    public function setUp(): void
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->plugin = \Mockery::mock('Plugin');

        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');

        $this->configuration = \Mockery::mock(Configuration::class)->makePartial();
        $this->configuration_class = \Mockery::mock(ConfigurationClass::class)->makePartial();
        $this->context_adapter = \Mockery::mock('Context');
        $this->context_adapter->cart = \Mockery::mock('Cart');
//        $this->context_adapter->language = \Mockery::mock('Language');
//        $this->context_adapter->language
//            ->shouldReceive(['iso_code'=> 'FR']);
        $this->context_adapter->cart->shouldReceive([
            'getOrderTotal' => 42.42,
        ]);
        // byDefault() lets individual tests override this with their own double
        // (e.g. one that supports ->getContext()->smarty) without affecting the
        // other tests that rely on the plain ContextMock.
        $this->context_adapter->shouldReceive('get')
            ->andReturn(ContextMock::get())
            ->byDefault();
        $this->dispatcher = \Mockery::mock('Dispatcher');
        $this->instance = \Mockery::mock('Instance');
        $this->controller = \Mockery::mock('Controller');
        $this->dispatcher->shouldReceive([
            'getInstance' => $this->instance,
            'getController' => $this->controller,
        ]);

        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->payment_method->shouldReceive([
            'getOperations' => ['x3_with_fees', 'x3_without_fees', 'x4_with_fees', 'x4_without_fees'],
        ]);

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive([
            'addLog' => true,
        ]);
        $this->plugin->shouldReceive([
            'getConfiguration' => $this->configuration,
            'getConfigurationClass' => $this->configuration_class,
            'getContext' => $this->context_adapter,
            'getDispatcher' => $this->dispatcher,
            'getLogger' => $logger,
            'getPaymentMethodClass' => $this->payment_method_class,
            'getTools' => $this->toolsAdapter,
            'getInstance' => $this->instance,
            'getController' => $this->controller,
        ]);

        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->configuration->shouldReceive('getValue');

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });

        $this->action = \Mockery::mock(OneyAction::class, [$this->dependencies])->makePartial();
    }
}
