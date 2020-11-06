<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetDateAddTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->cache->setDateAdd('2020-12-31 23:59:42');
    }

    public function testReturnDateAdd(): void
    {
        $this->assertSame(
            '2020-12-31 23:59:42',
            $this->cache->getDateAdd()
        );
    }

    public function testDateAddIsAString(): void
    {
        $this->assertIsString(
            $this->cache->getDateAdd()
        );
    }

    public function testDateAddHaveAValidDatetimeFormat(): void
    {
        $this->assertMatchesRegularExpression(
            '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',
            $this->cache->getDateAdd()
        );
    }
}
