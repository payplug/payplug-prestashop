<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

final class GetLoggerTest extends TestCase
{
    protected $cache;
    protected $logger;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->logger = new LoggerEntity();
        $this->cache->setLogger($this->logger);
    }

    public function testReturnLogger(): void
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->cache->getLogger()
        );
    }
}
