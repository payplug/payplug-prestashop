<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class SetIdPayPlugCacheTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setIdPayplugCache('test_id');
    }

    public function testUpdateCacheId(): void
    {
        $this->cache->setIdPayplugCache('another_id');
        $this->assertSame(
            'another_id',
            $this->cache->getIdPayplugCache()
        );
    }

    public function testReturnCacheEntity(): void
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setIdPayplugCache('another_id')
        );
    }

    public function testThrowExceptionWhenNotAString(): void
    {
        $this->expectException(TypeError::class);
        $this->cache->setIdPayplugCache(42);
    }
}
