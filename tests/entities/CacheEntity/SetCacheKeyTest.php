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

    public function testReturnCacheKey(): void
    {
        $this->cache->setCacheKey('another_key');
        $this->assertSame(
            'another_key',
            $this->cache->getCacheKey()
        );
    }
}
