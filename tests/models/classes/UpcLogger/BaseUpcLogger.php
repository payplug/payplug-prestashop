<?php

namespace PayPlug\tests\models\classes\UpcLogger;

use PayPlug\src\models\classes\UpcLogger;
use PHPUnit\Framework\TestCase;

abstract class BaseUpcLogger extends TestCase
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

        $this->logger = new UpcLogger($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
