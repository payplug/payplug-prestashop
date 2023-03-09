<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\utilities\services\Routes;
use PayPlug\src\utilities\validators\moduleValidator;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseApiRest extends TestCase
{
    public $amount_helper;
    public $classe;
    public $configuration;
    public $configuration_action;
    public $configuration_class;
    public $constant;
    public $dependencies;
    public $logger;
    public $plugin;
    public $tools;

    protected function setUp()
    {
        $this->logger = \Mockery::mock('Logger');
        $this->logger
            ->shouldReceive([
                'addLog' => true,
            ]);

        $this->configuration = \Mockery::mock('Configuration');
        $this->configuration
            ->shouldReceive([
                'get' => true,
            ]);

        $this->constant = \Mockery::mock('Constant');
        $this->constant
            ->shouldReceive('get')
            ->andReturnUsing(function ($key) {
                return $key;
            })
        ;

        $this->configuration_action = \Mockery::mock('ConfigurationAction');
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->version = 'x.xx.xx';
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

        $this->configuration_class = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $this->configuration,
                'getConfigurationAction' => $this->configuration_action,
                'getConfigurationClass' => $this->configuration_class,
                'getConstant' => $this->constant,
                'getLogger' => $this->logger,
                'getRoutes' => \Mockery::mock(Routes::class)->makePartial(),
                'getTranslation' => $this->translation,
            ]);

        $this->amount_helper = \Mockery::mock(AmountHelper::class)->makePartial();
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => ['module' => \Mockery::mock(moduleValidator::class)->makePartial()],
                'getHelpers' => ['amount' => $this->amount_helper],
            ]);

        $this->tools = MockHelper::createToolsMock('PayPlug\src\application\adapter\ToolsAdapter');

        $this->classe = \Mockery::mock(ApiRest::class, [$this->dependencies])->makePartial();
    }
}
