<?php

namespace PayPlug\tests\unified\contract\UnifiedLock;

use PayPlug\src\unified\contract\UnifiedLock;
use PHPUnit\Framework\TestCase;

abstract class BaseUnifiedLock extends TestCase
{
    protected $dependencies;
    protected $lock;
    protected $module;
    protected $module_adapter;
    protected $plugin;
    protected $unified_lock_repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->plugin = \Mockery::mock('Plugin');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module = \Mockery::mock('PayplugModule');
        $this->unified_lock_repository = \Mockery::mock('UnifiedLockRepository');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getModule')->andReturn($this->module_adapter);
        $this->module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($this->module);
        $this->module->shouldReceive('getService')
            ->with('payplug.models.repositories.unified_lock')
            ->andReturn($this->unified_lock_repository);

        $this->lock = new UnifiedLock($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
