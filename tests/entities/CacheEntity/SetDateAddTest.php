<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

final class SetDateAddTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateAdd('2020-12-31 23:59:42');
    }

    public function testUpdateDateAdd(): void
    {
        $this->cache->setDateAdd('1920-12-31 23:59:42');
        $this->assertSame(
            '1920-12-31 23:59:42',
            $this->cache->getDateAdd()
        );
    }

    public function testReturnCacheEntity(): void
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setDateAdd('1920-12-31 23:59:42')
        );
    }

    public function testThrowExceptionWhenNotAString(): void
    {
        $this->expectException(TypeError::class);
        $this->cache->setDateAdd(42);
    }

    public function testThrowExceptionWhenNotWellFormatted(): void
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setDateAdd('1er Janvier 1970');
    }
}
