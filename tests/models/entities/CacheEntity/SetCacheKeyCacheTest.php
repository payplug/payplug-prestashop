<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\exceptions\BadParameterException;
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

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param $cache_key
     */
    public function testThrowExceptionWhenNotAString($cache_key)
    {
        try {
            $this->cache->setCacheKey($cache_key);
        } catch (BadParameterException $e) {
            $this->assertSame('Invalid argument, $cache_key must be a string.', $e->getMessage());
        }
    }
}
