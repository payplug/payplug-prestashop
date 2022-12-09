<?php

use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetDateUpdCacheTest extends TestCase
{
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateUpd('2021-12-31 23:59:42');
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->cache->getDateUpd()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->cache->getDateUpd())
        );
    }

    public function testDateUpdHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->cache->getDateUpd()
        );
    }
}
