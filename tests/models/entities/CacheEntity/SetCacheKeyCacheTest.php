<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetCacheKeyCacheTest extends BaseCacheEntity
{
    public function testUpdateCacheKey()
    {
        $this->cache->setCacheKey('another_key');
        $this->assertSame(
            'another_key',
            $this->cache->getCacheKey()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setCacheKey('another_key')
        );
    }
}
