<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class SetDateAddTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateAdd('test_date');
    }

    public function testUpdateDateAdd(): void
    {
        $this->cache->setDateAdd('another_date');
        $this->assertSame(
            'another_date',
            $this->cache->getDateAdd()
        );
    }
}
