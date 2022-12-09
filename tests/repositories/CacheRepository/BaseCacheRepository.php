<?php

namespace PayPlug\tests\repositories\CacheRepository;

use PayPlug\src\models\entities\CardEntity;
use PayPlug\src\repositories\CacheRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\RepositoryBase;

class BaseCacheRepository extends RepositoryBase
{
    protected $cacheEntity;

    public function setUp()
    {
        parent::setUp();

        $this->cacheEntity = new CardEntity();
        $this->logger->shouldReceive([
            'setProcess' => $this->logger,
        ]);

        $this->repo = \Mockery::mock(CacheRepository::class, [
            $this->cacheEntity,
            $this->query,
            $this->config,
            $this->dependencies,
            $this->logger,
            $this->constant,
        ])->makePartial();

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);
    }
}
