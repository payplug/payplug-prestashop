<?php declare(strict_types=1);

use PayPlug\src\entities\PluginEntity;
use PHPUnit\Framework\TestCase;

final class SetApiVersionTest extends TestCase
{
    public function test_update_the_api_version(): void
    {
        $plugin = new PluginEntity();
        $plugin->setApiVersion('test_version');
        $this->assertSame(
            'test_version',
            $plugin->getApiVersion()
        );
    }
}