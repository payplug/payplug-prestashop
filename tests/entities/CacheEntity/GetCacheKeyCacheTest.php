<?php

use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetCacheKeyCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheKey('test_key');
    }

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
