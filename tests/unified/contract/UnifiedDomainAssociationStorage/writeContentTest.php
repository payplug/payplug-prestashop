<?php

namespace PayPlug\tests\unified\contract\UnifiedDomainAssociationStorage;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;

/**
 * @group unit
 * @group unified
 */
class writeContentTest extends BaseUnifiedDomainAssociationStorage
{
    public function testStoresTheContentBase64EncodedInTheGlobalScope()
    {
        $this->storage->writeContent("<b>ABCD</b>\n");

        $this->assertSame(
            [UnifiedDomainAssociationStorage::CONTENT_KEY => base64_encode("<b>ABCD</b>\n")],
            $this->rows
        );
        $this->assertSame('PAYPLUG_APPLE_PAY_WELL_KNOWN', UnifiedDomainAssociationStorage::CONTENT_KEY);
    }

    public function testThrowsWhenTheContentCannotBeWritten()
    {
        $this->write_succeeds = false;

        $this->expectException(\RuntimeException::class);

        $this->storage->writeContent('ABCD');
    }
}
