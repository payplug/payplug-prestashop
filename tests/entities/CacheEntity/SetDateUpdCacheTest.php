<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetDateUpdCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateUpd('2021-12-31 23:59:42');
    }

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
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setDateUpd(42);
    }

    /**
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setDateUpd('1er Janvier 1970');
    }
}
