<?php

namespace PayPlug\tests\unified\contract\UnifiedConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getPublicKeyValueTest extends BaseUnifiedConfigurationRepository
{
    public function testIsObsoleteAndReturnsEmptyString()
    {
        $this->assertSame('', $this->repository->getPublicKeyValue());
    }
}
