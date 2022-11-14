<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneySimulationsTest extends BaseOneyRepository
{
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

        $this->config->shouldReceive('get')
            ->with('PAYPLUG_SANDBOX_MODE')
            ->andReturn(false)
        ;

        $this->logger->shouldReceive([
            'setProcess' => $this->logger,
        ]);

        $this->simulations = OneySimulationsMock::get()[$this->operation];
    }

    public function testWithoutInvalidCacheKey()
    {
        $this->cache
            ->shouldReceive([
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
        $this->cache
            ->shouldReceive([
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
        $this->cache
            ->shouldReceive([
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

        $this->dependencies->apiClass->shouldReceive([
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

        $this->assertSame(count($this->arrayLogger), 1);
    }

    public function testWithSimulationReturningError()
    {
        $this->cache
            ->shouldReceive([
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

        $this->dependencies->apiClass->shouldReceive([
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
        $this->cache
            ->shouldReceive([
                'setCacheKey' => [
                    'result' => $this->cacheKey,
                    'message' => 'Success',
                ],
                'getCacheByKey' => [
                    'result' => false,
                    'message' => 'No cache found',
                ],
                'setCache' => false,
            ])
        ;

        $this->dependencies->apiClass->shouldReceive([
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
        $this->assertSame(count($this->arrayLogger), 1);
    }

    public function testWhenSimulationCached()
    {
        $this->cache
            ->shouldReceive([
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

        $this->dependencies->apiClass->shouldReceive([
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
        $this->assertSame(count($this->arrayLogger), 0);
    }
}
