<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class SetDateUpdTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateUpd('test_date');
    }

    public function testUpdateDateUpd(): void
    {
        $this->cache->setDateUpd('another_date');
        $this->assertSame(
            'another_date',
            $this->cache->getDateUpd()
        );
    }
}
