<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use PayPlug\src\actions\ConfigurationAction;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\Translation;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseConfigurationAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $api_service;
    public $configuration;
    public $configuration_class;
    public $dependencies;
    public $logger;
    public $module;
    public $module_adapter;
    public $oney;
    public $plugin;
    public $validator;
    public $validate_adapter;

    public function setUp()
    {
        $this->configuration = \Mockery::mock(Configuration::class)->makePartial();
        $this->configuration->shouldReceive([
            'get' => true,
        ]);

        $this->api_service = \Mockery::mock('ApiService');
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->oney = \Mockery::mock('Oney');
        $this->plugin = \Mockery::mock('Plugin');
        $this->validate_adapter = \Mockery::mock('ValidateAdapter');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            })
        ;

        $this->dependencies->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                return $key;
            })
        ;

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });

        $this->configuration_class = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();

        $this->module = \Mockery::mock('Module');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.service.api')
            ->andReturn($this->api_service);

        $this->plugin->shouldReceive([
            'getLogger' => $this->logger,
            'getConfiguration' => $this->configuration,
            'getConfigurationClass' => $this->configuration_class,
            'getOney' => $this->oney,
            'getTranslationClass' => $this->translation,
            'getModule' => $this->module_adapter,
            'getValidate' => $this->validate_adapter,
        ]);

        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->dependencies->name = 'payplug';

        $this->dependencies->shouldReceive('l')
            ->andReturnUsing(function ($key) {
                return $key;
            })
        ;

        $this->action = \Mockery::mock(ConfigurationAction::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
