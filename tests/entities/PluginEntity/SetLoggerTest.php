<?php declare(strict_types=1);

use PayPlug\src\entities\LoggerEntity;
use PayPlug\src\entities\PluginEntity;
use PHPUnit\Framework\TestCase;

final class SetLoggerTest extends TestCase
{
    public function test_update_the_logger_entity(): void
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