<?php

namespace PayPlug\tests\unified\contract\UnifiedConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getTest extends BaseUnifiedConfigurationRepository
{
    public function testReturnsNullWhenConfigurationValueIsEmpty()
    {
        $this->configuration_adapter->shouldReceive('get')->with('PAYPLUG_UPC_FOO')->andReturn(false);

        $this->assertNull($this->repository->get('foo'));
    }

    public function testReturnsTheStoredValue()
    {
        $this->configuration_adapter->shouldReceive('get')->with('PAYPLUG_UPC_FOO')->andReturn('bar');

        $this->assertSame('bar', $this->repository->get('foo'));
    }
}
