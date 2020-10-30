<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

final class SetTableTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setTable('test_table');
    }

    public function testUpdateTable(): void
    {
        $this->cache->setTable('another_table');
        $this->assertSame(
            'another_table',
            $this->cache->getTable()
        );
    }

    public function testReturnCacheEntity(): void
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setTable('another_table')
        );
    }

    public function testThrowExceptionWhenNotAString(): void
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setTable(42);
    }
}
