<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\models\entities\CacheEntity;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseCacheEntity extends TestCase
{
    use FormatDataProvider;
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheKey('test_key');
        $this->cache->setCacheValue('test_value');
        $this->cache->setDateAdd('2021-12-31 23:59:42');
        $this->cache->setDateUpd('2021-12-31 23:59:42');
    }
}
