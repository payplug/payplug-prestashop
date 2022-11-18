<?php

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
