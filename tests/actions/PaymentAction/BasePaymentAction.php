<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\src\actions\PaymentAction;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BasePaymentAction extends TestCase
{
    use FormatDataProvider;

    protected $action;
    protected $api_service;
    protected $cartAdapter;
    protected $configClass;
    protected $configuration;
    protected $context;
    protected $dependencies;
    protected $logger;
    protected $order_class;
    protected $payment_method;
    protected $payment_method_class;
    protected $payment_repository;
    protected $payment_validator;
    protected $plugin;
    protected $toolsAdapter;

    public function setUp()
    {
        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration->shouldReceive([
            'get' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": true, "amex": true}');
        $this->configuration->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(true);

        $this->api_service = \Mockery::mock('ApiService');
        $this->cartAdapter = \Mockery::mock('CartAdapter');
        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');

        $this->plugin = \Mockery::mock('Plugin');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->configClass = \Mockery::mock('ConfigClass');
        $this->dependencies->configClass = $this->configClass;

        $this->context = \Mockery::mock('Context');
        $this->context->shouldReceive([
            'get' => ContextMock::get(),
        ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->order_class = \Mockery::mock('OrderClass');

        $this->payment_method_class = \Mockery::mock(PaymentMethod::class, [$this->dependencies])->makePartial();
        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->payment_repository = \Mockery::mock('PaymentRepository');

        $this->plugin->shouldReceive([
            'getApiService' => $this->api_service,
            'getCart' => $this->cartAdapter,
            'getContext' => $this->context,
            'getLogger' => $this->logger,
            'getTools' => $this->toolsAdapter,
            'getConfigurationClass' => $this->configuration,
            'getOrderClass' => $this->order_class,
            'getPaymentMethodClass' => $this->payment_method_class,
            'getPaymentRepository' => $this->payment_repository,
        ]);

        $this->dependencies->name = 'payplug';

        $this->payment_validator = \Mockery::mock('PaymentValidator');
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
            'getValidators' => [
                'payment' => $this->payment_validator,
            ],
        ]);

        $this->action = \Mockery::mock(PaymentAction::class, [$this->dependencies])->makePartial();
    }
}
