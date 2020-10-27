<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetCacheValueTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheValue('test_value');
    }

    public function testReturnCacheValue(): void
    {
        $this->assertSame(
            'test_value',
            $this->cache->getCacheValue()
        );
    }
}
