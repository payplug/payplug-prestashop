<?php

namespace PayPlug\tests\models\classes\OrderLock;

use PayPlug\src\models\classes\OrderLock;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

abstract class BaseOrderLock extends TestCase
{
    use FormatDataProvider;

    protected $class;
    protected $dependencies;
    protected $logger;
    protected $plugin;
    protected $query_adapter;

    public function setUp(): void
    {
        $this->dependencies = \Mockery::mock('DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive('addLog')
            ->andReturn(true)
            ->byDefault();
        $this->query_adapter = \Mockery::mock('QueryAdapter');
        $this->query_adapter->shouldReceive('getLastError')
            ->andReturn('')
            ->byDefault();
        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getLogger' => $this->logger,
            'getQueryAdapter' => $this->query_adapter,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->class = \Mockery::mock(OrderLock::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->dependencies = $this->dependencies;
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }
}
