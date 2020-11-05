<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetCacheKeyTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheKey('test_key');
    }

    public function testReturnCacheKey(): void
    {
        $this->assertSame(
            'test_key',
            $this->cache->getCacheKey()
        );
    }

    public function testCacheKeyIsAString(): void
    {
        $this->assertIsString(
            $this->cache->getCacheKey()
        );
    }
}
