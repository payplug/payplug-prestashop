<?php

namespace PayPlug\tests\unified\contract\UnifiedDomainAssociationStorage;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;
use PHPUnit\Framework\TestCase;

abstract class BaseUnifiedDomainAssociationStorage extends TestCase
{
    protected $configuration_adapter;
    protected $dependencies;
    protected $plugin;
    protected $rows = [];
    protected $storage;
    protected $write_succeeds = true;

    public function setUp(): void
    {
        parent::setUp();
        $this->rows = [];
        $this->write_succeeds = true;

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->plugin = \Mockery::mock('Plugin');
        $this->configuration_adapter = \Mockery::mock('ConfigurationAdapter');

        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getConfiguration')->andReturn($this->configuration_adapter);

        $this->configuration_adapter->shouldReceive('getGlobalValue')->andReturnUsing(function ($key) {
            return array_key_exists($key, $this->rows) ? $this->rows[$key] : false;
        });
        $this->configuration_adapter->shouldReceive('updateValue')->andReturnUsing(
            function ($key, $value, $id_shop_group = null, $id_shop = null) {
                $this->assertSame([0, 0], [$id_shop_group, $id_shop], 'The value must be written in the global scope.');
                if (!$this->write_succeeds) {
                    return false;
                }
                $this->rows[$key] = $value;

                return true;
            }
        );

        $this->storage = new UnifiedDomainAssociationStorage($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
