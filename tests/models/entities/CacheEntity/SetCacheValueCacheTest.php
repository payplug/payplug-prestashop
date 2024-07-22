<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetCacheValueCacheTest extends BaseCacheEntity
{
    public function testUpdateCacheValue()
    {
        $this->cache->setCacheValue('another_value');
        $this->assertSame(
            'another_value',
            $this->cache->getCacheValue()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setCacheValue('another_key')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $cache_value
     */
    public function testSetCacheValueInvalidType($cache_value)
    {
        try {
            $this->cache->setCacheValue($cache_value);
        } catch (BadParameterException $e) {
            $this->assertSame('Invalid argument, $cache_value must be a string.', $e->getMessage());
        }
    }
}
