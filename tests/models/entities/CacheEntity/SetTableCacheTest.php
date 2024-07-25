<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetTableCacheTest extends BaseCacheEntity
{
    protected function setUp()
    {
        parent::setUp();
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
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $table
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString($table)
    {
        $this->expectExceptionMessage('Invalid argument, $table must be a string');
        $this->expectException(BadParameterException::class);
        $this->cache->setTable($table);
    }
}
