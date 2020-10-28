<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class SetCacheKeyTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheKey('test_key');
    }

    public function testUpdateCacheKey(): void
    {
        $this->cache->setCacheKey('another_key');
        $this->assertSame(
            'another_key',
            $this->cache->getCacheKey()
        );
    }

    public function testReturnCacheEntity(): void
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setCacheKey('another_key')
        );
    }

    public function testThrowExceptionWhenNotAString(): void
    {
        $this->expectException(TypeError::class);
        $this->cache->setCacheKey(42);
    }
}
