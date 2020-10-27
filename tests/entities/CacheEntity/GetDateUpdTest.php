<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetDateUpdTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateUpd('test_date');
    }

    public function testReturnDateUpd(): void
    {
        $this->assertSame(
            'test_date',
            $this->cache->getDateUpd()
        );
    }
}
