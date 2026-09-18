<?php

namespace PayPlug\tests\models\classes\UpcLock;

use PayPlug\src\models\classes\UpcLock;
use PHPUnit\Framework\TestCase;

abstract class BaseUpcLock extends TestCase
{
    protected $dependencies;
    protected $lock;
    protected $module;
    protected $module_adapter;
    protected $plugin;
    protected $upc_lock_repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->plugin = \Mockery::mock('Plugin');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module = \Mockery::mock('PayplugModule');
        $this->upc_lock_repository = \Mockery::mock('UpcLockRepository');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getModule')->andReturn($this->module_adapter);
        $this->module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($this->module);
        $this->module->shouldReceive('getService')
            ->with('payplug.models.repositories.upc_lock')
            ->andReturn($this->upc_lock_repository);

        $this->lock = new UpcLock($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
