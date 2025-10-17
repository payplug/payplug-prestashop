<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetCacheValueCacheTest extends BaseCacheEntity
{
    public function testReturnCacheValue()
    {
        $this->assertSame(
            'test_value',
            $this->cache->getCacheValue()
        );
    }

    public function testCacheValueIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getCacheValue())
        );
    }
}
