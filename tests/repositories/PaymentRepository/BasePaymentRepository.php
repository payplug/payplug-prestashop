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

namespace PayPlug\tests\repositories\PaymentRepository;

use Mockery;
use Payplug\Payment;
use PayPlug\src\models\entities\PaymentEntity;
use PayPlug\src\repositories\PaymentRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\RepositoryBase;

class BasePaymentRepository extends RepositoryBase
{
    protected $paymentEntity;
    protected $paymentApi;

    public function setUp()
    {
        parent::setUp();

        $this->paymentEntity = $this->paymentEntity ? $this->paymentEntity : new PaymentEntity();
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');

        $this->logger->shouldReceive([
            'setParams' => $this->logger,
        ]);

        $this->repo = \Mockery::mock(PaymentRepository::class, [
            $this->cart,
            $this->config,
            $this->dependencies,
            $this->logger,
            $this->paymentEntity,
            $this->query,
            $this->constant,
        ])->makePartial();

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);

        $this->dependencies->paymentClass
            ->shouldReceive('setPaymentErrorsCookie')
            ->andReturn(true)
        ;

        $this->dependencies
            ->shouldReceive('l')
        ;

        $this->constant
            ->shouldReceive('get')
            ->andReturn('constant')
        ;

        $this->apiCall();
    }

    public function apiCall()
    {
        $this->paymentApi = Mockery::mock('alias:' . Payment::class);
    }
}
