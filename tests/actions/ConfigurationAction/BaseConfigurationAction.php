<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use PayPlug\src\actions\ConfigurationAction;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseConfigurationAction extends TestCase
{
    public $action;
    public $configuration;
    public $dependencies;
    public $logger;
    public $plugin;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->logger = \Mockery::mock('Logger');
        $this->logger
            ->shouldReceive([
                'addLog' => true,
            ]);

        $this->configuration = \Mockery::mock('Configuration');
        $this->configuration
            ->shouldReceive([
                'get' => true,
                'updateValue' => true,
            ]);

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getLogger' => $this->logger,
                'getConfiguration' => $this->configuration,
            ]);

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies
            ->shouldReceive([
                'getConfigurationKey' => true,
                'getPlugin' => $this->plugin,
            ]);

        $this->action = \Mockery::mock(ConfigurationAction::class, [$this->dependencies])->makePartial();
    }
}
