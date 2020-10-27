<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

final class SetLoggerTest extends TestCase
{
    protected $cache;
    protected $logger;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->logger = new LoggerEntity();
    }

    public function testReturnLogger(): void
    {
        $this->cache->setLogger($this->logger);
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->cache->getLogger()
        );
    }
}
