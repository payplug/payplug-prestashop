<?php

namespace PayPlug\tests\unified\contract\UnifiedTokenCache;

use PayPlug\src\unified\contract\UnifiedTokenCache;
use PHPUnit\Framework\TestCase;

abstract class BaseUnifiedTokenCache extends TestCase
{
    protected $cache;
    protected $cache_repository;
    protected $dependencies;
    protected $plugin;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->plugin = \Mockery::mock('Plugin');
        $this->cache_repository = \Mockery::mock('CacheRepository');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);
        $this->plugin->shouldReceive('getCacheRepository')->andReturn($this->cache_repository);

        $this->cache = new UnifiedTokenCache($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
