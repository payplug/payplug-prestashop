<?php

use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetTableCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
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
