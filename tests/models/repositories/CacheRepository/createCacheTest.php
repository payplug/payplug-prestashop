<?php

namespace PayPlug\tests\models\repositories\CacheRepository;

/**
 * @group unit
 * @group repository
 * @group cache_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class createCacheTest extends BaseCacheRepository
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $this->assertFalse($this->repository->createCache($parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'values' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->createCache($parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'values' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->createCache($parameters));
    }
}
