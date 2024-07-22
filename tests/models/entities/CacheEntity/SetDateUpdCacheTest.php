<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetDateUpdCacheTest extends BaseCacheEntity
{
    public function testUpdateDateUpd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->cache->getDateUpd()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setDateUpd('1920-12-31 23:59:42')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date_upd
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString($date_upd)
    {
        $this->expectExceptionMessage('Invalid argument, $date_upd must be at format: \'Y-m-d h:m:s\'');
        $this->expectException(BadParameterException::class);
        $this->cache->setDateUpd($date_upd);
    }

    /**
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectExceptionMessage('Invalid argument, $date_upd must be at format: \'Y-m-d h:m:s\'');
        $this->expectException(BadParameterException::class);
        $this->cache->setDateUpd('1er Janvier 1970');
    }
}
