<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetTableTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setTable('test_table');
    }

    public function testReturnTable(): void
    {
        $this->assertSame(
            'test_table',
            $this->cache->getTable()
        );
    }

    public function testTableIsAString(): void
    {
        $this->assertIsString(
            $this->cache->getTable()
        );
    }
}
