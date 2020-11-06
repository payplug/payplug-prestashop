<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PayPlug\src\entities\PluginEntity;
use PHPUnit\Framework\TestCase;

final class SetCacheTest extends TestCase
{
    public function test_update_the_cache_entity(): void
    {
        $plugin = new PluginEntity();
        $cache = new CacheEntity();
        $plugin->setCache($cache);
        $this->assertInstanceOf(
            CacheEntity::class,
            $plugin->getCache()
        );
    }
}