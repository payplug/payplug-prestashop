<?php

namespace PayPlug\tests\models\classes\Configuration;

use PayPlug\src\models\classes\Configuration;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseConfiguration extends TestCase
{
    use FormatDataProvider;

    public $configuration;
    public $dependencies;
    public $classe;

    protected function setUp()
    {
        $this->configuration = \Mockery::mock('Configuration');
        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $this->configuration,
            ]);
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);
        $this->classe = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
    }
}
