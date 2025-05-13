<?php

namespace PayPlug\tests\actions\MerchantTelemetryAction;

use PayPlug\src\actions\MerchantTelemetryAction;
use PayPlug\src\models\classes\Configuration;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseMerchantTelemetryAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $api_service;
    public $configuration;
    public $constant;
    public $dependencies;
    public $logger;
    public $module;
    public $module_repository;
    public $plugin;
    public $repositories;
    public $shop_repository;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';

        $this->plugin = \Mockery::mock('Plugin');

        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration->shouldReceive([
            'getCurrentConfigurations' => [],
        ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->module_repository = \Mockery::mock('ModuleRepository');
        $this->module_repository->shouldReceive([
            'getActiveModule' => [
                [
                    'name' => 'module_1',
                    'version' => '1.0.0',
                ],
                [
                    'name' => 'module_2',
                    'version' => '2.0.0',
                ],
                [
                    'name' => 'module_3',
                    'version' => '3.0.0',
                ],
            ],
        ]);

        $this->shop_repository = \Mockery::mock('ShopRepository');
        $this->shop_repository->shouldReceive([
            'getActiveShopUrl' => [
                [
                    'url' => 'website.domain.1.com',
                    'default' => true,
                ],
                [
                    'url' => 'website.domain.2.com',
                    'default' => false,
                ],
                [
                    'url' => 'website.domain.3.com',
                    'default' => false,
                ],
            ],
        ]);

        $this->module = \Mockery::mock('Module');
        $instance = \Mockery::mock('Payplug');
        $instance->version = '1.0.0';
        $this->module->shouldReceive([
            'getInstanceByName' => $instance,
        ]);

        $this->api_service = \Mockery::mock('ApiService');
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);
        $instance
            ->shouldReceive('getService')
            ->with('payplug.utilities.service.api')
            ->andReturn($this->api_service);

        $this->constant = \Mockery::mock('Constant');
        $this->constant->shouldReceive('get')
            ->with('PHP_VERSION')
            ->andReturn('');

        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getConstant' => $this->constant,
            'getLogger' => $this->logger,
            'getModule' => $this->module,
            'getModuleRepository' => $this->module_repository,
            'getShopRepository' => $this->shop_repository,
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;

        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->action = \Mockery::mock(MerchantTelemetryAction::class, [$this->dependencies])->makePartial();
    }
}
