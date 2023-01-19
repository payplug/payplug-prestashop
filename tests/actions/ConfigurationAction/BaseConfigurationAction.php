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
    public $module;
    public $oney;
    public $plugin;
    public $validator;

    protected function setUp()
    {
        $this->configuration = \Mockery::mock('Configuration');
        $this->configuration
            ->shouldReceive([
                'get' => true,
            ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger
            ->shouldReceive([
                'addLog' => true,
            ]);

        $module = \Mockery::mock('PrestashopModule');
        $module
            ->shouldReceive([
                'enable' => true,
            ]);

        $this->module = \Mockery::mock('Module');
        $this->module
            ->shouldReceive([
                'getInstanceByName' => $module,
            ]);

        $this->oney = \Mockery::mock('Oney');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getLogger' => $this->logger,
                'getConfiguration' => $this->configuration,
                'getOney' => $this->oney,
                'getModule' => $this->module,
            ]);

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => [
                    'payment' => \Mockery::mock(paymentValidator::class)->makePartial(),
                ],
            ]);

        $this->dependencies->name = 'payplug';

        $this->dependencies
            ->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                return $key;
            })
        ;

        $this->action = \Mockery::mock(ConfigurationAction::class, [$this->dependencies])->makePartial();
    }
}
