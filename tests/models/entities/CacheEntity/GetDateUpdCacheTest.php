<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetDateUpdCacheTest extends BaseCacheEntity
{
    public function testReturnDateUpd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->cache->getDateUpd()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getDateUpd())
        );
    }

    public function testDateUpdHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->cache->getDateUpd()
        );
    }
}
