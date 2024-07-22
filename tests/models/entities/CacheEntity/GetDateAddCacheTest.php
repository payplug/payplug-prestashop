<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetDateAddCacheTest extends BaseCacheEntity
{
    public function testReturnDateAdd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->cache->getDateAdd()
        );
    }

    public function testDateAddIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getDateAdd())
        );
    }

    public function testDateAddHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->cache->getDateAdd()
        );
    }
}
