<?php

use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetCacheValueCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheValue('test_value');
    }

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
