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

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->plugin = \Mockery::mock('Plugin');

        $this->configuration = \Mockery::mock('ConfigurationClass');
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->payment_action = \Mockery::mock('PaymentAction');
        $this->payment_repository = \Mockery::mock('PaymentRepository');

        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getLogger' => $this->logger,
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
