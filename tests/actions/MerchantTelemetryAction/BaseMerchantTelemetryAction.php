<?php

namespace PayPlug\tests\actions\MerchantTelemetryAction;

use PayPlug\src\actions\MerchantTelemetryAction;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\repositories\ModuleRepository;
use PayPlug\src\models\repositories\ShopRepository;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseMerchantTelemetryAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $configuration;
    public $dependencies;
    public $module;
    public $module_repositories;
    public $plugin;
    public $repositories;
    public $shop_repositories;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';

        $this->plugin = \Mockery::mock('Plugin');

        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration->shouldReceive([
            'getCurrentConfigurations' => [],
        ]);

        $this->module_repositories = \Mockery::mock(ModuleRepository::class)->makePartial();
        $this->module_repositories
            ->shouldReceive([
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

        $this->shop_repositories = \Mockery::mock(ShopRepository::class)->makePartial();
        $this->shop_repositories
            ->shouldReceive([
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

        $this->repositories = [
            'module' => $this->module_repositories,
            'shop' => $this->shop_repositories,
        ];

        $this->module = \Mockery::mock('Module');
        $instance = \Mockery::mock('Payplug');
        $instance->version = '1.0.0';
        $this->module
            ->shouldReceive([
            'getInstanceByName' => $instance,
        ]);

        $this->plugin
            ->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getModule' => $this->module,
        ]);

        $this->dependencies
            ->shouldReceive([
            'getPlugin' => $this->plugin,
            'getRepositories' => $this->repositories,
        ]);

        $this->action = \Mockery::mock(MerchantTelemetryAction::class, [$this->dependencies])->makePartial();
    }
}
