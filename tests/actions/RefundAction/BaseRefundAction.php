<?php

namespace PayPlug\tests\actions\RefundAction;

use PayPlug\src\actions\RefundAction;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\src\models\classes\Translation;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseRefundAction extends TestCase
{
    use FormatDataProvider;

    protected $api_service;
    protected $action;
    protected $configuration;
    protected $dependencies;
    protected $logger;
    protected $payment_method;
    protected $payment_method_class;
    protected $payment_repository;
    protected $plugin;
    protected $tools_adapter;
    protected $payment_validator;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->plugin = \Mockery::mock('Plugin');
        $this->action = \Mockery::mock(RefundAction::class, [$this->dependencies])->makePartial();
        $this->tools_adapter = \Mockery::mock('ToolsAdapter');
        $this->payment_validator = \Mockery::mock('PaymentValidator');
        $this->dependencies->installmentClass = \Mockery::mock('InstallmentClass');
        $this->configuration = \Mockery::mock('ConfigurationClass');
        $this->configuration->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(true);
        $this->configuration->shouldReceive('getValue')
            ->with('test_api_key')
            ->andReturn('test_api_key');
        $this->configuration->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn('live_api_key');

        $this->payment_method_class = \Mockery::mock(PaymentMethod::class, [$this->dependencies])->makePartial();

        $this->api_service = \Mockery::mock('ApiService');
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->payment_repository = \Mockery::mock('PaymentRepository');

        $this->dependencies->shouldReceive(
            [
                'getPlugin' => $this->plugin,
                'getValidators' => [
                    'payment' => $this->payment_validator,
                ],
            ]
        );
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);
        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation->shouldReceive('l')
            ->andReturnUsing(
                function ($str) {
                    return $str;
                }
            );

        $this->plugin->shouldReceive([
            'getApiService' => $this->api_service,
            'getConfigurationClass' => $this->configuration,
            'getPaymentMethodClass' => $this->payment_method_class,
            'getPaymentRepository' => $this->payment_repository,
            'getLogger' => $this->logger,
            'getTools' => $this->tools_adapter,
        ]);
    }
}
