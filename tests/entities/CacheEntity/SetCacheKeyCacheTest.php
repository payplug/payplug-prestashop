<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetCacheKeyCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheKey('test_key');
    }

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
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testTrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setCacheKey(42);
    }
}
