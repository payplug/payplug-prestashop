<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

use PayPlug\src\actions\ApplePayDomainAssociationAction;
use PHPUnit\Framework\TestCase;

abstract class BaseApplePayDomainAssociationAction extends TestCase
{
    protected $action;
    protected $constant_adapter;
    protected $dependencies;
    protected $factory;
    protected $logger;
    protected $logs = [];
    protected $module;
    protected $module_adapter;
    protected $plugin;
    protected $service;
    protected $storage;

    public function setUp(): void
    {
        parent::setUp();
        $this->logs = [];

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->plugin = \Mockery::mock('Plugin');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module = \Mockery::mock('Module');
        $this->factory = \Mockery::mock('UnifiedApiPaymentServiceFactory');
        $this->service = \Mockery::mock('DomainAssociationService');
        $this->storage = \Mockery::mock('UnifiedDomainAssociationStorage');
        $this->logger = \Mockery::mock('Logger');
        $this->constant_adapter = \Mockery::mock('ConstantAdapter');

        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getModule')->andReturn($this->module_adapter);
        $this->plugin->shouldReceive('getLogger')->andReturn($this->logger);
        $this->plugin->shouldReceive('getConstant')->andReturn($this->constant_adapter);
        $this->module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($this->module);
        $this->module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($this->factory);
        $this->factory->shouldReceive('createDomainAssociationService')->andReturn($this->service);
        $this->factory->shouldReceive('createDomainAssociationStorage')->andReturn($this->storage);
        $this->logger->shouldReceive('addLog')->andReturnUsing(function ($message, $level = 'info') {
            $this->logs[] = ['level' => $level, 'message' => $message];

            return true;
        });

        $this->action = \Mockery::mock(ApplePayDomainAssociationAction::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->action->dependencies = $this->dependencies;
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * @description Make the module container return another factory
     *
     * @param mixed $factory
     * @param mixed $logger defaults to the logger collecting $this->logs
     */
    protected function useFactory($factory, $logger = null)
    {
        $module = \Mockery::mock('Module');
        $module->shouldReceive('getService')->with('payplug.unified.payment_service_factory')->andReturn($factory);
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getLogger')->andReturn(null === $logger ? $this->logger : $logger);
        $plugin->shouldReceive('getConstant')->andReturn($this->constant_adapter);
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $this->action->dependencies = $dependencies;
    }

    protected function assertErrorLogged()
    {
        $this->assertNotEmpty($this->logs, 'An error should have been logged.');
        $this->assertSame('error', $this->logs[0]['level']);
    }
}
