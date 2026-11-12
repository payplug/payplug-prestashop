<?php

namespace PayPlug\tests\unified\contract\UnifiedLogger;

use PayPlug\src\unified\contract\UnifiedLogger;
use PHPUnit\Framework\TestCase;

abstract class BaseUnifiedLogger extends TestCase
{
    protected $dependencies;
    protected $logger;
    protected $logger_repository;
    protected $plugin;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->plugin = \Mockery::mock('Plugin');
        $this->logger_repository = \Mockery::mock('LoggerRepository');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getLogger')->andReturn($this->logger_repository);

        $this->logger = new UnifiedLogger($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
