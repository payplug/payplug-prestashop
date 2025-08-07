<?php

namespace PayPlug\tests\actions\HookAction;

use PayPlug\src\actions\HookAction;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseHookAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $configuration;
    public $dependencies;
    public $logger;
    public $plugin;
    public $payment_action;
    public $payment_repository;
    public $merchant_class;
    public $module;
    public $module_adapter;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->plugin = \Mockery::mock('Plugin');

        $this->configuration = \Mockery::mock('ConfigurationClass');
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->module = \Mockery::mock('Module');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);

        $this->merchant_class = \Mockery::mock('MerchantClass');
        $this->merchant_class->shouldReceive([
            'isLogged' => true,
        ]);
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.models.classes.merchant')
            ->andReturn($this->merchant_class);

        $this->payment_action = \Mockery::mock('PaymentAction');
        $this->payment_repository = \Mockery::mock('PaymentRepository');

        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getLogger' => $this->logger,
            'getModule' => $this->module_adapter,
            'getPaymentAction' => $this->payment_action,
            'getPaymentRepository' => $this->payment_repository,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);
        $this->action = \Mockery::mock(HookAction::class)->makePartial();
        $this->action->dependencies = $this->dependencies;
    }
}
