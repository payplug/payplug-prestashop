<?php

namespace PayPlug\tests\unified\contract\UnifiedDomainAssociationStorage;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;

/**
 * @group unit
 * @group unified
 */
class purgeTest extends BaseUnifiedDomainAssociationStorage
{
    public function testDeletesTheContentAndTheStatus()
    {
        $this->configuration_adapter->shouldReceive('deleteByName')
            ->once()
            ->with(UnifiedDomainAssociationStorage::CONTENT_KEY)
            ->andReturn(true);
        $this->configuration_adapter->shouldReceive('deleteByName')
            ->once()
            ->with(UnifiedDomainAssociationStorage::STATUS_KEY)
            ->andReturn(true);

        $this->assertTrue($this->storage->purge());
    }

    public function testStillDeletesTheStatusWhenTheContentDeletionFails()
    {
        $this->configuration_adapter->shouldReceive('deleteByName')
            ->once()
            ->with(UnifiedDomainAssociationStorage::CONTENT_KEY)
            ->andReturn(false);
        $this->configuration_adapter->shouldReceive('deleteByName')
            ->once()
            ->with(UnifiedDomainAssociationStorage::STATUS_KEY)
            ->andReturn(true);

        $this->assertFalse($this->storage->purge());
    }

    public function testFailsWhenTheStatusDeletionFails()
    {
        $this->configuration_adapter->shouldReceive('deleteByName')
            ->with(UnifiedDomainAssociationStorage::CONTENT_KEY)
            ->andReturn(true);
        $this->configuration_adapter->shouldReceive('deleteByName')
            ->with(UnifiedDomainAssociationStorage::STATUS_KEY)
            ->andReturn(false);

        $this->assertFalse($this->storage->purge());
    }
}
