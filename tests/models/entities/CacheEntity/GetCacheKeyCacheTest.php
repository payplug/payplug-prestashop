<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetCacheKeyCacheTest extends BaseCacheEntity
{
    public function testReturnCacheKey()
    {
        $this->assertSame(
            'test_key',
            $this->cache->getCacheKey()
        );
    }

    public function testCacheKeyIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getCacheKey())
        );
    }
}
