<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetIdPayPlugCacheCacheTest extends BaseCacheEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->cache->setIdPayPlugCache('test_id');
    }

    public function testCacheIdIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getIdPayPlugCache())
        );
    }
}
