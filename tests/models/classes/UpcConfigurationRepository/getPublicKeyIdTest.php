<?php

namespace PayPlug\tests\models\classes\UpcConfigurationRepository;

/**
 * @group unit
 * @group unified
 */
class getPublicKeyIdTest extends BaseUpcConfigurationRepository
{
    public function testIsObsoleteAndReturnsEmptyString()
    {
        $this->assertSame('', $this->repository->getPublicKeyId());
    }
}
