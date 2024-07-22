<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetTableCacheTest extends BaseCacheEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->cache->setTable('test_table');
    }

    public function testReturnTable()
    {
        $this->assertSame(
            'test_table',
            $this->cache->getTable()
        );
    }

    public function testTableIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getTable())
        );
    }
}
