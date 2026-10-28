<?php

namespace PayPlug\tests\unified\contract\UnifiedDomainAssociationStorage;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;

/**
 * @group unit
 * @group unified
 */
class writeStatusTest extends BaseUnifiedDomainAssociationStorage
{
    public function testStoresTheStatusAsJsonWithoutMarkupCharacters()
    {
        $this->storage->writeStatus([
            'lastAttemptAt' => 20,
            'lastSuccessAt' => null,
            'lastError' => '<script>alert(1)</script> & "q" \'a\' < unclosed',
        ]);

        $stored = $this->rows[UnifiedDomainAssociationStorage::STATUS_KEY];
        $this->assertSame('PAYPLUG_APPLE_PAY_WELL_KNOWN_STATUS', UnifiedDomainAssociationStorage::STATUS_KEY);
        foreach (['<', '>', '&', "'", '\\"q\\"'] as $character) {
            $this->assertStringNotContainsString($character, $stored);
        }
    }

    public function testThrowsWhenTheStatusCannotBeWritten()
    {
        $this->write_succeeds = false;

        $this->expectException(\RuntimeException::class);

        $this->storage->writeStatus(['lastAttemptAt' => 1, 'lastSuccessAt' => 1, 'lastError' => null]);
    }

    public function testThrowsWhenTheStatusCannotBeEncoded()
    {
        $this->expectException(\RuntimeException::class);

        $this->storage->writeStatus(['lastAttemptAt' => 1, 'lastSuccessAt' => null, 'lastError' => "invalid \xB1 utf-8"]);
    }
}
