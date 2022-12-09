<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetTableCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setTable('test_table');
    }

    public function testUpdatePaymentTable()
    {
        $this->cache->setTable('another_table');
        $this->assertSame(
            'another_table',
            $this->cache->getTable()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setTable('another_table')
        );
    }

    /**
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setTable(42);
    }
}
