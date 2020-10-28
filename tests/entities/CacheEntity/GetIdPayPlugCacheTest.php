<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetIdPayPlugCacheTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setIdPayPlugCache('test_id');
    }

    public function testReturnCacheId(): void
    {
        $this->assertSame(
            'test_id',
            $this->cache->getIdPayPlugCache()
        );
    }

    public function testCacheIdIsAString(): void
    {
        $this->assertIsString(
            $this->cache->getIdPayPlugCache()
        );
    }
}
