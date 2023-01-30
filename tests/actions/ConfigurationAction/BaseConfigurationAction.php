<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use PayPlug\src\actions\ConfigurationAction;
use PayPlug\src\models\classes\Translation;
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

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies
            ->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            })
        ;

        $this->dependencies
            ->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                return $key;
            })
        ;

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();

        $this->plugin
            ->shouldReceive([
                'getLogger' => $this->logger,
                'getConfiguration' => $this->configuration,
                'getOney' => $this->oney,
                'getTranslation' => $this->translation,
                'getModule' => $this->module,
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);

        $this->dependencies->name = 'payplug';

        $this->dependencies->name = 'payplug';

        $this->dependencies
            ->shouldReceive('l')
            ->andReturnUsing(function ($key) {
                return $key;
            })
        ;

        $this->action = \Mockery::mock(ConfigurationAction::class, [$this->dependencies])->makePartial();
    }
}
