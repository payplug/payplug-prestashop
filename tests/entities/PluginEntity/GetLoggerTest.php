<?php declare(strict_types=1);

use PayPlug\src\entities\LoggerEntity;
use PayPlug\src\entities\PluginEntity;
use PHPUnit\Framework\TestCase;

final class GetLoggerTest extends TestCase
{
    public function test_return_a_logger_entity(): void
    {
        $plugin = new PluginEntity();
        $logger = new LoggerEntity();
        $plugin->setLogger($logger);
        $this->assertInstanceOf(
            LoggerEntity::class,
            $plugin->getLogger()
        );
    }
}