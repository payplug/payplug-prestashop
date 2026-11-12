<?php

namespace PayPlug\tests\unified\contract\UnifiedConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getPublicKeyIdTest extends BaseUnifiedConfigurationRepository
{
    public function testIsObsoleteAndReturnsEmptyString()
    {
        $this->assertSame('', $this->repository->getPublicKeyId());
    }
}
