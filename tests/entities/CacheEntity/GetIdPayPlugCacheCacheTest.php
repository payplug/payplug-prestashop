<?php

use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetIdPayPlugCacheCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setIdPayPlugCache('test_id');
    }

    public function testReturnCacheId()
    {
        $this->assertSame(
            'test_id',
            $this->cache->getIdPayPlugCache()
        );
    }

    public function testCacheIdIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getIdPayPlugCache())
        );
    }
}
