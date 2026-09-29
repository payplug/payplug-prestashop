<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use PayPlug\src\actions\ConfigurationAction;
use PHPUnit\Framework\TestCase;

/**
 * EntityRepository::initialize()/uninstall() only create/drop the tables of repositories whose
 * class is already loaded (get_declared_classes()). Repositories registered only in
 * config/services.yml must therefore be loaded here, or their table is silently missing on a
 * fresh install (the 5.2.0 upgrade script creating them with raw SQL hides it on upgrades).
 *
 * @group unit
 * @group action
 * @group configuration_action
 */
class forceAutoloadUpcRepositoriesTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testLoadsEveryServicesOnlyUpcRepository()
    {
        $action = (new \ReflectionClass(ConfigurationAction::class))->newInstanceWithoutConstructor();
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        foreach (['operation', 'upc_lock', 'alias', 'upc_refund'] as $repository) {
            $module->shouldReceive('getService')->once()->with('payplug.models.repositories.' . $repository);
        }

        $property = new \ReflectionProperty(ConfigurationAction::class, 'dependencies');
        $property->setAccessible(true);
        $property->setValue($action, $dependencies);

        $method = new \ReflectionMethod(ConfigurationAction::class, 'forceAutoloadUpcRepositories');
        $method->setAccessible(true);
        $method->invoke($action);

        $this->addToAssertionCount(1);
    }
}
