<?php

namespace PayPlug\tests\unified\contract\UnifiedDomainAssociationStorage;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;

/**
 * @group unit
 * @group unified
 */
class readStatusTest extends BaseUnifiedDomainAssociationStorage
{
    public function statusProvider()
    {
        yield 'success' => [['lastAttemptAt' => 10, 'lastSuccessAt' => 10, 'lastError' => null]];

        yield 'failure after a success' => [['lastAttemptAt' => 20, 'lastSuccessAt' => 10, 'lastError' => 'Unexpected HTTP status 500 from secure.payplug.com.']];

        yield 'error with markup and quotes' => [['lastAttemptAt' => 20, 'lastSuccessAt' => null, 'lastError' => '<script>alert(1)</script> & "q" \'a\'']];

        yield 'error with an unclosed tag' => [['lastAttemptAt' => 20, 'lastSuccessAt' => null, 'lastError' => 'Source unreachable: Foo: bad < thing']];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testReturnsTheWrittenStatus(array $status)
    {
        $this->storage->writeStatus($status);

        $this->assertSame($status, $this->storage->readStatus());
    }

    public function testReturnsAnEmptyArrayWhenNothingIsStored()
    {
        $this->assertSame([], $this->storage->readStatus());
    }

    public function invalidStoredStatusProvider()
    {
        yield 'empty string' => [''];

        yield 'invalid json' => ['{not json'];

        yield 'json string' => ['"a string"'];

        yield 'json number' => ['42'];

        yield 'not a string' => [['lastAttemptAt' => 1]];
    }

    /**
     * @dataProvider invalidStoredStatusProvider
     *
     * @param mixed $stored
     */
    public function testReturnsAnEmptyArrayWhenTheStoredStatusIsInvalid($stored)
    {
        $this->rows[UnifiedDomainAssociationStorage::STATUS_KEY] = $stored;

        $this->assertSame([], $this->storage->readStatus());
    }
}
