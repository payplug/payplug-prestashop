<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getOneySimulationsTest extends BaseOneyPaymentMethod
{
    public $amount;
    public $country;
    public $operation;
    public $cache;
    public $simulations;

    public function setUp()
    {
        parent::setUp();
        $this->amount = 42;
        $this->country = 'FR';
        $this->operation = [
            'oney_operation',
        ];
        $this->simulations = OneySimulationsMock::get();
        $this->cache = \Mockery::mock('Cache');
        $this->plugin->shouldReceive([
            'getCache' => $this->cache,
        ]);
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsntValidIntegerFormat($amount)
    {
        $this->assertSame(
            [
                'result' => false,
                'error' => '$amount is not a valid int',
            ],
            $this->class->getOneySimulations($amount, $this->country, $this->operation)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $country
     */
    public function testWhenGivenCountryIsntValidStringFormat($country)
    {
        $this->assertSame(
            [
                'result' => false,
                'error' => '$country is not a valid string',
            ],
            $this->class->getOneySimulations($this->amount, $country, $this->operation)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $operation
     */
    public function testWhenGivenOperationIsntValidArrayFormat($operation)
    {
        $this->assertSame(
            [
                'result' => false,
                'error' => '$operation is not a valid array',
            ],
            $this->class->getOneySimulations($this->amount, $this->country, $operation)
        );
    }

    public function testWhenCacheKeyCantBeSetted()
    {
        $this->cache->shouldReceive([
            'setCacheKey' => [
                'result' => false,
                'message' => 'an error occurred',
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'error' => 'an error occurred',
            ],
            $this->class->getOneySimulations($this->amount, $this->country, $this->operation)
        );
    }

    public function testWhenCacheExists()
    {
        $this->cache->shouldReceive([
            'setCacheKey' => [
                'result' => true,
            ],
            'getCacheByKey' => [
                'result' => OneySimulationsMock::getFromCache(),
                'message' => 'Success',
            ],
        ]);
        $this->assertSame(
            [
                'result' => true,
                'simulations' => OneySimulationsMock::get(),
            ],
            $this->class->getOneySimulations($this->amount, $this->country, $this->operation)
        );
    }

    public function testWithExceptionThrowed()
    {
        $this->cache->shouldReceive([
            'setCacheKey' => [
                'result' => true,
            ],
            'getCacheByKey' => [
                'result' => false,
                'message' => 'No cache found',
            ],
        ]);

        $this->api_service->shouldReceive([
            'getOneySimulations' => [
                'code' => 403,
                'result' => false,
                'message' => 'Payplug\Exception\HttpException: [0]: Forbidden method; HTTP Response: 403',
            ],
        ]);

        $this->assertSame(
            $this->class->getOneySimulations($this->amount, $this->country, $this->operation),
            [
                'result' => false,
                'error' => 'Payplug\Exception\HttpException: [0]: Forbidden method; HTTP Response: 403',
            ]
        );
    }

    public function testWithSimulationReturningError()
    {
        $this->cache->shouldReceive([
            'setCacheKey' => [
                'result' => true,
            ],
            'getCacheByKey' => [
                'result' => false,
                'message' => 'No cache found',
            ],
        ]);

        $this->api_service->shouldReceive([
            'getOneySimulations' => [
                'code' => 200,
                'result' => true,
                'resource' => [
                    'object' => 'error',
                    'message' => 'error while getting simulations',
                ],
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'error while getting simulations',
            ],
            $this->class->getOneySimulations($this->amount, $this->country, $this->operation)
        );
    }

    public function testWhenSimulationCantBeCached()
    {
        $this->cache->shouldReceive([
            'setCacheKey' => [
                'result' => true,
            ],
            'getCacheByKey' => [
                'result' => false,
                'message' => 'No cache found',
            ],
            'setCache' => false,
        ]);

        $this->api_service->shouldReceive([
            'getOneySimulations' => [
                'code' => 200,
                'result' => true,
                'resource' => $this->simulations,
            ],
        ]);

        ksort($this->simulations);

        $this->assertSame(
            [
                'result' => true,
                'simulations' => $this->simulations,
            ],
            $this->class->getOneySimulations($this->amount, $this->country, $this->operation)
        );
    }

    public function testWhenSimulationCached()
    {
        $this->cache->shouldReceive([
            'setCacheKey' => [
                'result' => true,
            ],
            'getCacheByKey' => [
                'result' => false,
                'message' => 'No cache found',
            ],
            'setCache' => true,
        ]);

        $this->api_service->shouldReceive([
            'getOneySimulations' => [
                'code' => 200,
                'result' => true,
                'resource' => $this->simulations,
            ],
        ]);

        ksort($this->simulations);

        $this->assertSame(
            [
                'result' => true,
                'simulations' => $this->simulations,
            ],
            $this->class->getOneySimulations($this->amount, $this->country, $this->operation)
        );
    }
}
