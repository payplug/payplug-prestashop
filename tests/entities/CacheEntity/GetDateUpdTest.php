<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetDateUpdTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateUpd('2020-12-31 23:59:42');
    }

    public function testReturnDateUpd(): void
    {
        $this->assertSame(
            '2020-12-31 23:59:42',
            $this->cache->getDateUpd()
        );
    }

    public function testDateUpdIsAString(): void
    {
        $this->assertIsString(
            $this->cache->getDateUpd()
        );
    }

    public function testDateUpdHaveAValidDatetimeFormat(): void
    {
        $this->assertMatchesRegularExpression(
            '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',
            $this->cache->getDateUpd()
        );
    }
}
