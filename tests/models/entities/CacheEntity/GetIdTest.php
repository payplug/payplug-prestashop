<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetIdTest extends BaseCacheEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->cache->setId(123);
    }

    public function testCacheIdIsAnInteger()
    {
        $this->assertTrue(
            is_int($this->cache->getId())
        );
    }
}
