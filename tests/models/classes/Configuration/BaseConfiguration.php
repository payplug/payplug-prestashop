<?php

namespace PayPlug\tests\models\classes\Configuration;

use PayPlug\src\models\classes\Configuration;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseConfiguration extends TestCase
{
    use FormatDataProvider;

    protected $configuration;
    protected $dependencies;
    protected $class;

    public function setUp()
    {
        $this->configuration = \Mockery::mock('Configuration');
        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getConfiguration' => $this->configuration,
        ]);
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);
        $this->class = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
    }
}
