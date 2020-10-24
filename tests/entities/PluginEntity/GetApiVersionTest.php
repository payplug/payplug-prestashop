<?php declare(strict_types=1);

use PayPlug\src\entities\PluginEntity;
use PHPUnit\Framework\TestCase;

final class GetApiVersionTest extends TestCase
{
    public function test_return_an_api_version(): void
    {
        $plugin = new PluginEntity();
        $plugin->setApiVersion('test_version');
        $this->assertEquals(
            'test_version',
            $plugin->getApiVersion()
        );
    }
}