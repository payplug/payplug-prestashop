<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

final class SetDateUpdTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateUpd('2020-12-31 23:59:42');
    }

    public function testUpdateDateUpd(): void
    {
        $this->cache->setDateUpd('1920-12-31 23:59:42');
        $this->assertSame(
            '1920-12-31 23:59:42',
            $this->cache->getDateUpd()
        );
    }

    public function testReturnCacheEntity(): void
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setDateUpd('1920-12-31 23:59:42')
        );
    }

    public function testThrowExceptionWhenNotAString(): void
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setDateUpd(42);
    }

    public function testThrowExceptionWhenNotWellFormatted(): void
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setDateUpd('1er Janvier 1970');
    }
}
