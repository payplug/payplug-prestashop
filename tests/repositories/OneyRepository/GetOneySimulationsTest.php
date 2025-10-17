<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneySimulationsTest extends BaseOneyRepository
{
    use FormatDataProvider;
    protected $oneyMock;

    protected $amount = [
        'lower' => 99,
        'upper' => 3001,
        'default' => 500,
    ];
    protected $iso = 'FR';
    protected $operation = 'x3_with_fees';
    protected $operations = ['x3_with_fees', 'x4_with_fees'];
    protected $cacheKey = 'Payplug::OneySimulations_500_FR_x3_with_fees_live';
    protected $simulations;

    public function setUp()
    {
        parent::setUp();

        // Method setup
        $this->oneyMock = MockHelper::createMockFactory('alias:Payplug\OneySimulation');

        $this->simulations = OneySimulationsMock::get()[$this->operation];
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenPaymentIsInvalidIntegerFormat($amount)
    {
        $country = 'fr';
        $operation = [];

        $this->assertSame(
            [
                'result' => false,
                'error' => '$amount is not a valid int',
            ],
            $this->repo->getOneySimulations($amount, $country, $operation)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $country
     */
    public function testWhenGivenPaymentIsInvalidStringFormat($country)
    {
        $amount = 500;
        $operation = [];

        $this->assertSame(
            [
                'result' => false,
                'error' => '$country is not a valid string',
            ],
            $this->repo->getOneySimulations($amount, $country, $operation)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $operation
     */
    public function testWhenGivenPaymentIsInvalidArrayFormat($operation)
    {
        $amount = 500;
        $country = 'fr';

        $this->assertSame(
            [
                'result' => false,
                'error' => '$operation is not a valid array',
            ],
            $this->repo->getOneySimulations($amount, $country, $operation)
        );
    }

    public function testWithoutInvalidCacheKey()
    {
        $this->cache_adapter->shouldReceive([
            'setCacheKey' => [
                'result' => false,
                'message' => 'set cacheKey error message',
            ],
        ])
        ;

        $this->assertSame(
            [
                'result' => false,
                'error' => 'set cacheKey error message',
            ],
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation])
        );
    }

    public function testWithExistingCache()
    {
        $this->cache_adapter->shouldReceive([
            'setCacheKey' => [
                'result' => $this->cacheKey,
                'message' => 'Success',
            ],
            'getCacheByKey' => [
                'result' => OneySimulationsMock::getFromCache(),
                'message' => 'Success',
            ],
        ])
        ;

        $this->assertSame(
            [
                'result' => true,
                'simulations' => OneySimulationsMock::get(),
            ],
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation])
        );
    }

    public function testWithExceptionThrowed()
    {
        $this->cache_adapter->shouldReceive([
            'setCacheKey' => [
                'result' => $this->cacheKey,
                'message' => 'Success',
            ],
            'getCacheByKey' => [
                'result' => false,
                'message' => 'No cache found',
            ],
        ])
        ;

        $this->api_service->shouldReceive([
            'getOneySimulations' => [
                'code' => 403,
                'result' => false,
                'message' => 'Payplug\Exception\HttpException: [0]: Forbidden method; HTTP Response: 403',
            ],
        ]);

        $this->assertSame(
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation]),
            [
                'result' => false,
                'error' => 'Payplug\Exception\HttpException: [0]: Forbidden method; HTTP Response: 403',
            ]
        );
    }

    public function testWithSimulationReturningError()
    {
        $this->cache_adapter->shouldReceive([
            'setCacheKey' => [
                'result' => $this->cacheKey,
                'message' => 'Success',
            ],
            'getCacheByKey' => [
                'result' => false,
                'message' => 'No cache found',
            ],
        ])
        ;

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
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation])
        );
    }

    public function testWhenSimulationCantBeCached()
    {
        $this->cache_adapter->shouldReceive([
            'setCacheKey' => [
                'result' => $this->cacheKey,
                'message' => 'Success',
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
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation])
        );
    }

    public function testWhenSimulationCached()
    {
        $this->cache_adapter->shouldReceive([
            'setCacheKey' => [
                'result' => $this->cacheKey,
                'message' => 'Success',
            ],
            'getCacheByKey' => [
                'result' => false,
                'message' => 'No cache found',
            ],
            'setCache' => true,
        ])
        ;

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
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation])
        );
    }
}
