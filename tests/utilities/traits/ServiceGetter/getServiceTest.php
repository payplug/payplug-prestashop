<?php

namespace PayPlug\tests\utilities\traits\ServiceGetter;

use PayPlug\src\utilities\traits\ServiceGetter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class getServiceTest extends TestCase
{
    private $dependencies;
    private $module;
    private $module_adapter;
    private $user_of_trait;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->module = \Mockery::mock('Module');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive('getModule')->andReturn($this->module_adapter);
        $this->dependencies->shouldReceive('getPlugin')->andReturn($plugin);

        $this->user_of_trait = new class($this->dependencies) {
            use ServiceGetter;

            public $dependencies;

            public function __construct($dependencies)
            {
                $this->dependencies = $dependencies;
            }

            public function callGetService($service_name)
            {
                return $this->getService($service_name);
            }
        };
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testReturnsTheServiceFromTheModuleNamedByTheDependencies()
    {
        $service = new \stdClass();
        $this->module_adapter->shouldReceive('getInstanceByName')->once()->with('payplug')->andReturn($this->module);
        $this->module->shouldReceive('getService')->once()->with('payplug.models.repositories.operation')->andReturn($service);

        $this->assertSame($service, $this->user_of_trait->callGetService('payplug.models.repositories.operation'));
    }

    public function testUsesTheCurrentModuleName()
    {
        $this->dependencies->name = 'pspaylater';
        $this->module_adapter->shouldReceive('getInstanceByName')->once()->with('pspaylater')->andReturn($this->module);
        $this->module->shouldReceive('getService')->once()->with('payplug.action.operation')->andReturn('service');

        $this->assertSame('service', $this->user_of_trait->callGetService('payplug.action.operation'));
    }

    public function testLetsTheContainerExceptionPropagateForAnUnregisteredService()
    {
        $this->module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($this->module);
        $this->module->shouldReceive('getService')->with('payplug.unknown')->andThrow(new \RuntimeException('Service not found'));

        $this->expectException(\RuntimeException::class);

        $this->user_of_trait->callGetService('payplug.unknown');
    }
}
