<?php

namespace PayPlug\tests\models\classes\UpcConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class setTest extends BaseUpcConfigurationRepository
{
    public function testDelegatesToConfigurationAdapterWithPrefixedKey()
    {
        $this->configuration_adapter->shouldReceive('updateValue')->once()->with('PAYPLUG_UPC_FOO', 'bar');

        $this->repository->set('foo', 'bar');
        $this->addToAssertionCount(1);
    }
}
