<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\utilities\services\Routes;
use PayPlug\src\utilities\validators\moduleValidator;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseApiRest extends TestCase
{
    public $classe;
    public $configuration;
    public $configuration_action;
    public $constant;
    public $dependencies;
    public $logger;
    public $plugin;

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

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $this->configuration,
                'getConfigurationAction' => $this->configuration_action,
                'getConstant' => $this->constant,
                'getLogger' => $this->logger,
                'getRoutes' => \Mockery::mock(Routes::class)->makePartial(),
                'getTranslation' => $this->translation,
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => ['module' => \Mockery::mock(moduleValidator::class)->makePartial()],
                'getHelpers' => [],
            ]);

        $this->classe = \Mockery::mock(ApiRest::class, [$this->dependencies])->makePartial();
    }
}
