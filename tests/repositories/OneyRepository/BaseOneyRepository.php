<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\classes\AmountCurrencyClass;
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
            $this->validate
        ])->makePartial();

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);
    }
}
