<?php

namespace PayPlug\tests\models\classes\Hook;

use PayPlug\src\models\classes\Hook;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseHook extends TestCase
{
    use FormatDataProvider;

    protected $dependencies;
    protected $logger_adapter;
    protected $class;
    protected $plugin;
    protected $configClass;

    public function setUp()
    {
        $this->dependencies = \Mockery::mock('DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->configClass = \Mockery::mock('ConfigClass');
        $this->dependencies->configClass = $this->configClass;

        $this->logger_adapter = \Mockery::mock('LoggerAdapter');
        $this->plugin = \Mockery::mock('Plugin');

        $this->logger_adapter->shouldReceive([
            'addLog' => true,
        ]);
        $this->plugin->shouldReceive([
            'getLogger' => $this->logger_adapter,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->class = \Mockery::mock(Hook::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->dependencies = $this->dependencies;
    }
}
