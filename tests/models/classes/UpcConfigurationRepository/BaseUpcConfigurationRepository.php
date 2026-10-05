<?php

namespace PayPlug\tests\models\classes\UpcConfigurationRepository;

use PayPlug\src\models\classes\UpcConfigurationRepository;
use PHPUnit\Framework\TestCase;

abstract class BaseUpcConfigurationRepository extends TestCase
{
    protected $configuration_adapter;
    protected $dependencies;
    protected $logger;
    protected $merchant;
    protected $module;
    protected $module_adapter;
    protected $plugin;
    protected $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->plugin = \Mockery::mock('Plugin');
        $this->configuration_adapter = \Mockery::mock('ConfigurationAdapter');
        $this->logger = \Mockery::mock('LoggerRepository');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module = \Mockery::mock('PayplugModule');
        $this->merchant = \Mockery::mock('Merchant');

        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getConfiguration')->andReturn($this->configuration_adapter);
        $this->plugin->shouldReceive('getLogger')->andReturn($this->logger);
        $this->plugin->shouldReceive('getModule')->andReturn($this->module_adapter);
        $this->module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($this->module);
        $this->module->shouldReceive('getService')
            ->with('payplug.models.classes.merchant')
            ->andReturn($this->merchant);
        $this->logger->shouldReceive('addLog');

        $this->repository = new UpcConfigurationRepository($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
