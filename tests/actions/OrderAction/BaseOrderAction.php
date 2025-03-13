<?php

namespace PayPlug\tests\actions\OrderAction;

use PayPlug\src\actions\OrderAction;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseOrderAction extends TestCase
{
    use FormatDataProvider;

    protected $cart_adapter;
    protected $configuration_class;
    protected $customer_adapter;
    protected $dependencies;
    protected $logger;
    protected $module;
    protected $module_adapter;
    protected $order_adapter;
    protected $order_class;
    protected $order_repository;
    protected $payment_method;
    protected $payment_method_class;
    protected $payment_repository;
    protected $payment_validator;
    protected $payplug_order_state_repository;
    protected $plugin;
    protected $validate_adapter;

    public function setUp()
    {
        $this->cart_adapter = \Mockery::mock('CartAdapter');
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->customer_adapter = \Mockery::mock('CustomerAdapter');
        $this->logger = \Mockery::mock('Logger');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->order_class = \Mockery::mock('OrderClass');
        $this->order_repository = \Mockery::mock('OrderRepository');
        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->payment_repository = \Mockery::mock('PaymentRepository');
        $this->payplug_order_state_repository = \Mockery::mock('StateRepository');
        $this->validate_adapter = \Mockery::mock('ValidateAdapter');

        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->module = \Mockery::mock('PrestashopModule');
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);

        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getCart' => $this->cart_adapter,
            'getConfigurationClass' => $this->configuration_class,
            'getCustomer' => $this->customer_adapter,
            'getLogger' => $this->logger,
            'getModule' => $this->module_adapter,
            'getOrder' => $this->order_adapter,
            'getOrderClass' => $this->order_class,
            'getOrderRepository' => $this->order_repository,
            'getStateRepository' => $this->payplug_order_state_repository,
            'getPaymentMethodClass' => $this->payment_method_class,
            'getPaymentRepository' => $this->payment_repository,
            'getValidate' => $this->validate_adapter,
        ]);

        $this->payment_validator = \Mockery::mock('PaymentValidator');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
            'getValidators' => [
                'payment' => $this->payment_validator,
            ],
        ]);

        $context = \Mockery::mock('Context');
        $context->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $context,
        ]);

        $this->action = \Mockery::mock(OrderAction::class, [$this->dependencies])->makePartial();
    }
}
