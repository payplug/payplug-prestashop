<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetDateAddCacheTest extends BaseCacheEntity
{
    public function testUpdateDateAdd()
    {
        $this->cache->setDateAdd('1920-12-31 23:59:42');
        $this->assertSame(
            '1920-12-31 23:59:42',
            $this->cache->getDateAdd()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setDateAdd('1920-12-31 23:59:42')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date_add
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString($date_add)
    {
        $this->expectExceptionMessage('Invalid argument, $date_add must be at format: \'Y-m-d h:m:s\'');
        $this->expectException(BadParameterException::class);
        $this->cache->setDateAdd($date_add);
    }

    /**
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectExceptionMessage('Invalid argument, $date_add must be at format: \'Y-m-d h:m:s\'');
        $this->expectException(BadParameterException::class);
        $this->cache->setDateAdd('1er Janvier 1970');
    }
}
