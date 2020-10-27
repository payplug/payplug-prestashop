<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetIdPayPlugCacheTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setIdPayplugCache('test_id');
    }

    public function testReturnCacheId(): void
    {
        $this->assertEquals(
            'test_id',
            $this->cache->getIdPayplugCache()
        );
    }
}
