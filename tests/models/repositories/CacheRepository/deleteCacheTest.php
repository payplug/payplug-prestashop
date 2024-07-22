<?php

namespace PayPlug\tests\models\repositories\CacheRepository;

/**
 * @group unit
 * @group repository
 * @group lock_repository
 *
 * @runTestsInSeparateProcesses
 */
class deleteCacheTest extends BaseCacheRepository
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $cache_key
     */
    public function testWhenGivenIdCountryIsInvalidIntegerFormat($cache_key)
    {
        $this->assertFalse($this->repository->deleteCache($cache_key));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $cache_key = 42;
        $this
            ->repository
            ->shouldReceive([
                'getEntityObject' => $this->entity,
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->deleteCache($cache_key));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $cache_key = 'lorem';
        $this
            ->repository
            ->shouldReceive([
                'getEntityObject' => $this->entity,
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->deleteCache($cache_key));
    }
}
