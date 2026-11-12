<?php

namespace PayPlug\tests\repositories\CacheRepository;

use PayPlug\src\repositories\CacheRepository;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group repository
 * @group cache_repository
 */
class flushCacheTest extends TestCase
{
    private $cache_model_repository;
    private $logger;
    private $repository;

    public function setUp(): void
    {
        $dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->cache_model_repository = \Mockery::mock('CacheModelRepository');

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive([
            'getCacheRepository' => $this->cache_model_repository,
        ]);
        $dependencies->shouldReceive([
            'getPlugin' => $plugin,
        ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive('setProcess');

        $this->repository = new CacheRepository(
            \Mockery::mock('CacheEntity'),
            \Mockery::mock('ConfigurationClass'),
            $dependencies,
            $this->logger
        );
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testUnifiedEntriesAreKeptWhenCacheIsFlushed()
    {
        $this->cache_model_repository->shouldNotReceive('deleteAll');
        $this->cache_model_repository->shouldReceive('deleteAllExceptKeyPrefix')
            ->once()
            ->with('upc_token:')
            ->andReturn(true);

        $this->assertTrue($this->repository->flushCache());
    }

    public function testFlushFailureIsLogged()
    {
        $this->cache_model_repository->shouldReceive('deleteAllExceptKeyPrefix')
            ->once()
            ->andReturn(false);
        $this->logger->shouldReceive('addLog')
            ->once()
            ->with(\Mockery::type('string'), 'error');

        $this->assertFalse($this->repository->flushCache());
    }
}
