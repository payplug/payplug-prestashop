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

namespace PayPlug\tests\repositories\CardRepository;

use Mockery;
use Payplug\Payment;
use PayPlug\src\models\entities\CardEntity;
use PayPlug\src\repositories\CardRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\RepositoryBase;

class BaseCardRepository extends RepositoryBase
{
    protected $cardApi;
    protected $cardEntity;
    protected $paymentApi;

    public function setUp()
    {
        parent::setUp();

        $this->cardEntity = new CardEntity();

        $this->config
            ->shouldReceive('get')
        ;

        $this->constant
            ->shouldReceive('get')
        ;

        $this->logger
            ->shouldReceive('setParams')
        ;

        $this->repo = Mockery::mock(CardRepository::class, [
            $this->config,
            $this->constant,
            $this->dependencies,
            $this->logger,
            $this->query,
            $this->tools,
        ])->makePartial();

        $this->arrayLogger = [];
        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);

        $this->cardApi = Mockery::mock('alias:' . Card::class);

        $this->paymentApi = Mockery::mock('alias:' . Payment::class);
    }
}
