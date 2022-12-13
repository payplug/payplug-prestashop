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

                    case 'PAYPLUG_ONEY_MIN_AMOUNTS':
                        return 'EUR:10000';

                    case 'PAYPLUG_ONEY_MAX_AMOUNTS':
                        return 'EUR:300000';

                    case 'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS':
                        return 'EUR:100';

                    case 'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS':
                        return 'EUR:3000';

                    case 'PS_SHOP_NAME':
                        return 'Payplug';

                    case 'PAYPLUG_ONEY_ALLOWED_COUNTRIES':
                        return 'FR';

                    case 'PAYPLUG_ONEY_FEES':
                        return true;

                    case 'PAYPLUG_SANDBOX_MODE':
                        return false;

                    default:
                        return true;
                }
            });
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
