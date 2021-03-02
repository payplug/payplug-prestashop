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

use PayPlug\src\entities\OneyEntity;
use PayPlug\src\specific\AddressSpecific;
use PayPlug\src\specific\CarrierSpecific;
use PayPlug\src\specific\CartSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\src\specific\ToolsSpecific;
use PayPlug\src\specific\ValidateSpecific;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\OneySimulationsMock;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\tests\repositories\OneyRepository\OneyBaseTest;


/**
 * @group uni
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneySimulationsTest extends OneyBaseTest
{
    protected $oney;

    protected $repo;

    protected $amount = [
        'lower' => 99,
        'upper' => 3001,
        'default' => 500
    ];
    protected $iso = 'FR';
    protected $operation = 'x3_with_fees';
    protected $operations = ['x3_with_fees','x4_with_fees'];

    protected $arrayCache;
    protected $arrayLogger;

    public function setUp()
    {
        parent::setUp();

        // Method setup
        $this->oney = MockHelper::createMockFactory('Payplug\OneySimulation');

        $this->cache->shouldReceive('setCacheKey')
            ->andReturnUsing(function ($amount, $country, $operations) {
                $cache_id = 'Payplug::OneySimulations_' .
                    (int)$amount . '_' .
                    (string)$country . '_' .
                    (string)implode('_', $operations) . '_' .
                    ($this->config->get('PAYPLUG_SANDBOX_MODE') ? 'test' : 'live');
                return ['result' => $cache_id];
            });

        $this->config->shouldReceive('get')
            ->with('PAYPLUG_SANDBOX_MODE')
            ->andReturn(false);

        $this->logger->shouldReceive('setParams')
            ->andReturn(true);

        $this->repo = new OneyRepository(
            $this->cache,
            $this->logger,
            new AddressSpecific(),
            new CartSpecific(),
            new CarrierSpecific(),
            $this->config,
            new ContextSpecific(),
            new CountrySpecific(),
            $this->tools,
            new ValidateSpecific(),
            new OneyEntity(),
            $this->myLogPhp,
            $this->payplug
        );

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);
    }

    public function testGetOneySimulationsWithoutCacheValid()
    {
        MockHelper::createSetCacheMock($this->cache, $this->arrayCache);

        $simulations = OneySimulationsMock::get()[$this->operation];

        $this->cache
            ->shouldReceive('getCacheByKey')
            ->andReturn(false);

        $this->oney->shouldReceive('getSimulations')
            ->andReturn($simulations);

        foreach($this->operations as $operation) {
            $this->assertEquals(
                $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$operation]),
                [
                    'result' => true,
                    'simulations' => $simulations
                ]
            );
        }

        $this->assertSame(count($this->arrayCache), count($this->operations));
        $this->assertSame(count($this->arrayLogger), 0);
    }

    public function testGetOneySimulationsWithCacheValid()
    {
        MockHelper::createSetCacheMock($this->cache, $this->arrayCache);

        $cache = OneySimulationsMock::getFromCache();
        $simulations = OneySimulationsMock::get();

        $this->cache
            ->shouldReceive('getCacheByKey')
            ->andReturn($cache);

        $this->assertEquals(
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation]),
             [
                'result' => true,
                'simulations' => $simulations
             ]
        );

        $this->assertSame(count($this->arrayLogger), 0);
        $this->assertSame(count($this->arrayCache), 0);
    }

    public function testGetOneySimulationsThrowException()
    {
        MockHelper::createSetCacheMock($this->cache, $this->arrayCache);

        $this->cache
            ->shouldReceive('getCacheByKey')
            ->andReturn(null);

        $this->oney->shouldReceive('getSimulations')
            ->andThrow('Payplug\Exception\HttpException', 'Forbidden method', 403);

        $this->assertSame(
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation]),
            [
                'result' => false,
                'error' => 'Payplug\Exception\HttpException: [0]: Forbidden method; HTTP Response: 403'
            ]
        );

        $this->assertSame(count($this->arrayLogger), 1);
        $this->assertSame(count($this->arrayCache), 0);
    }

    public function testGetOneySimulationsCantSetCache()
    {
        $simulations = OneySimulationsMock::get()[$this->operation];

        $this->cache
            ->shouldReceive('getCacheByKey')
            ->andReturn(false);

        $this->oney->shouldReceive('getSimulations')
            ->andReturn($simulations);

        $this->cache->shouldReceive('setCache')
            ->andReturn(false);

        $this->assertEquals(
            $this->repo->getOneySimulations($this->amount['default'], $this->iso, [$this->operation]),
            [
                'result' => true,
                'simulations' => $simulations
            ]
        );

        $this->assertSame(count($this->arrayLogger), 1);
    }
}
