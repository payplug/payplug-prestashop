<?php

namespace PayPlug\tests\utilities\traits\ServiceGetter;

use PayPlug\src\utilities\traits\ServiceGetter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class getServiceTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testGetServiceResolvesTheNamedServiceFromTheModule()
    {
        $expected_service = new \stdClass();

        $module = \Mockery::mock('Module');
        $module->shouldReceive('getService')->once()->with('payplug.some.service')->andReturn($expected_service);

        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module_adapter->shouldReceive('getInstanceByName')->once()->with('payplug')->andReturn($module);

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive('getModule')->once()->andReturn($module_adapter);

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->shouldReceive('getPlugin')->once()->andReturn($plugin);

        $user_of_trait = new class() {
            use ServiceGetter;

            public $dependencies;

            public function callGetService($service_name)
            {
                return $this->getService($service_name);
            }
        };
        $user_of_trait->dependencies = $dependencies;

        $this->assertSame($expected_service, $user_of_trait->callGetService('payplug.some.service'));
    }
}
