<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\src\models\entities\OneyEntity;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\RepositoryBase;

class BaseOneyRepository extends RepositoryBase
{
    protected $oney;

    protected $arrayCache;
    protected $arrayLogger;

    public function setUp()
    {
        parent::setUp();

        $this->oney = $this->oney ? $this->oney : new OneyEntity();
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');

        $this->repo = \Mockery::mock(OneyRepository::class, [
            $this->address,
            $this->assign,
            $this->cache,
            $this->carrier,
            $this->cart,
            $this->config,
            $this->context,
            $this->country,
            $this->currency,
            $this->media,
            $this->dependencies,
            $this->logger,
            $this->myLogPhp,
            $this->oney,
            $this->tools,
            $this->validate,
        ])->makePartial();

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);
    }
}
