<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

final class SetCacheValueTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setCacheValue('test_value');
    }

    public function testUpdateCacheValue(): void
    {
        $this->cache->setCacheValue('another_value');
        $this->assertSame(
            'another_value',
            $this->cache->getCacheValue()
        );
    }

    public function testReturnCacheEntity(): void
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setCacheValue('another_key')
        );
    }

    public function testThrowExceptionWhenNotAString(): void
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setCacheValue(42);
    }
}
