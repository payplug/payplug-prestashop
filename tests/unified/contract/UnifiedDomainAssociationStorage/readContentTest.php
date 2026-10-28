<?php

namespace PayPlug\tests\unified\contract\UnifiedDomainAssociationStorage;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;

/**
 * @group unit
 * @group unified
 */
class readContentTest extends BaseUnifiedDomainAssociationStorage
{
    public function contentProvider()
    {
        yield 'hex with a trailing LF' => ["7B227073704964223A2231227D\n"];

        yield 'hex with a trailing CRLF' => ["7B227073704964223A2231227D\r\n"];

        yield 'hex without a trailing newline' => ['7B227073704964223A2231227D'];

        yield 'tags, entities and quotes' => ["<b>x</b>&amp; \"q\" 'a' < unclosed\n"];

        yield 'leading and trailing spaces' => ["  7B22 \t\n\n"];

        yield 'binary bytes' => ["\x00\xEF\xBB\xBF\xFF\n"];

        yield 'maximum size accepted by UPC' => [str_repeat('A', 65536)];

        yield 'repository domain association file' => [
            file_get_contents(__DIR__ . '/../../../../.well-known/apple-developer-merchantid-domain-association'),
        ];
    }

    /**
     * @dataProvider contentProvider
     *
     * @param string $content
     */
    public function testReturnsTheWrittenContentByteForByte($content)
    {
        $this->storage->writeContent($content);

        $this->assertSame($content, $this->storage->readContent());
    }

    public function testReturnsNullWhenNothingIsStored()
    {
        $this->assertNull($this->storage->readContent());
    }

    public function testReturnsNullWhenTheStoredValueIsEmpty()
    {
        $this->rows[UnifiedDomainAssociationStorage::CONTENT_KEY] = '';

        $this->assertNull($this->storage->readContent());
    }

    public function testReturnsNullWhenTheStoredValueIsNotBase64()
    {
        $this->rows[UnifiedDomainAssociationStorage::CONTENT_KEY] = '***not base64***';

        $this->assertNull($this->storage->readContent());
    }

    public function testReturnsNullWhenTheStoredValueIsNotAString()
    {
        $this->rows[UnifiedDomainAssociationStorage::CONTENT_KEY] = ['array'];

        $this->assertNull($this->storage->readContent());
    }
}
