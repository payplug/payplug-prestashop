<?php

namespace PayPlug\tests\repositories\CacheRepository;

/**
 * @group unit
 * @group repository
 * @group cache
 * @group cache_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetCacheByKeyTest extends BaseCacheRepository
{
    private $cacheKey;

    public function setUp()
    {
        parent::setUp();

        $this->cacheKey = 'Payplug::OneySimulations_15000_FR_operation_live';
        $this->constant
            ->shouldReceive('get')
            ->andReturn(true)
        ;
    }

    public function invalidDataProvider()
    {
        yield [false];
        yield [[]];
        yield [42];
        yield [''];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $cacheKey
     */
    public function testWithInvalidDataProvider($cacheKey)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid cache key format',
            ],
            $this->repo->getCacheByKey($cacheKey)
        );
    }

    public function testWithNoCacheFoundInDB()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false,
            ])
        ;

        $this->assertSame(
            [
                'result' => false,
                'message' => 'No cache found',
            ],
            $this->repo->getCacheByKey($this->cacheKey)
        );
    }

    public function testWithOutdatedCacheReturn()
    {
        $lifetime = new \DateInterval('P2D');
        $date_limit = new \DateTime('now');
        $date_limit->sub($lifetime);

        $cache = [
            'cache_key' => $this->cacheKey,
            'date_add' => $date_limit->format('Y-m-d H:i:s'),
            'date_upd' => $date_limit->format('Y-m-d H:i:s'),
        ];

        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => [$cache],
            ])
        ;

        $this->repo
            ->shouldReceive('deleteCacheByKey')
            ->andReturn(true)
        ;

        $this->assertSame(
            [
                'result' => false,
                'message' => 'The current cache has been deleted',
            ],
            $this->repo->getCacheByKey($this->cacheKey)
        );
    }

    public function testWithValidData()
    {
        $date_limit = new \DateTime('now');
        $cache = [
            'cache_key' => $this->cacheKey,
            'date_add' => $date_limit->format('Y-m-d H:i:s'),
            'date_upd' => $date_limit->format('Y-m-d H:i:s'),
        ];

        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => [$cache],
            ])
        ;

        $this->assertSame(
            [
                'result' => $cache,
                'message' => 'Success',
            ],
            $this->repo->getCacheByKey($this->cacheKey)
        );
    }
}
