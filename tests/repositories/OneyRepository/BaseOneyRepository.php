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

        $this->config
            ->shouldReceive('get')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'PS_CURRENCY_DEFAULT':
                        return 1;
                    case 'PS_SHOP_NAME':
                        return 'Payplug';
                    default:
                        return true;
                }
            });
        $this->repo = \Mockery::mock(OneyRepository::class, [
            $this->address, // $addressAdapter,
            $this->assign, // $assign,
            $this->cache, // $cache,
            $this->carrier, // $carrierAdapter,
            $this->cart, // $cartAdapter,
            $this->config, // $configurationAdapter,
            $this->context, // $contextAdapter,
            $this->country, // $countryAdapter,
            $this->currency, // $currencyAdapter,
            $this->media, // $mediaAdapter,
            $this->dependencies, // $dependencies,
            $this->logger, // $logger,
            $this->oney, // $oneyEntity,
            $this->tools, // $toolsAdapter,
            $this->validate, // $validateAdapter
        ])->makePartial();

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);
    }
}
