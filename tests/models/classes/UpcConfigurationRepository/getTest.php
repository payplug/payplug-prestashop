<?php

namespace PayPlug\tests\models\classes\UpcConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getTest extends BaseUpcConfigurationRepository
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
