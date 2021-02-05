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

use Payplug\Exception\HttpException;
use PayPlug\src\repositories\CacheRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\src\repositories\LoggerRepository;
use PayPlug\tests\mock\OneySimulationsMock;
use PayPlug\src\repositories\OneyRepository;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group repository
 * @group oney
 * @group oney_repository
 */
final class GetOneySimulationsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $cacheMock;
    protected $amount;
    protected $isoCode;
    protected $operations;
    protected $data;

    public function setUp()
    {
        $this->cacheMock = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository')
            ->shouldReceive('setCache')
            ->andReturn(true);

        $this->confSpecificMock = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific')
            ->shouldReceive('get')
            ->with('PAYPLUG_SANDBOX_MODE')
            ->andReturn(false);

        $this->myLogPhpMock = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        $this->loggerMock = MockHelper::createMockFactory('PayPlug\src\repositories\LoggerRepository')
            ->shouldReceive('setParams')
            ->andReturn(true);
    }

    public function testGetOneySimulationsWithoutCacheValid()
    {
        $operation = 'x3_with_fees';

        // Get a array of oney simulation fake values
        $simulationsCallBack = OneySimulationsMock::getOneySimulations()[$operation];

        // 1 - Mock getCacheByKey in order to NOT use cache
        $this->cacheMock
            ->shouldReceive('getCacheByKey')
            ->andReturn(false);

        // 2 - Mock payplug-php getSimulations callback with a valid result
        $this->oneyMock = Mockery::mock('alias:Payplug\OneySimulation')
            ->shouldReceive('getSimulations')
            ->andReturn($simulationsCallBack);

        // 3 - Initiate OneyRepository class
        $oney_repo = new OneyRepository('');

        // Check assert
        $this->assertEquals(
            $oney_repo->getOneySimulations(500, 'FR', [$operation]),
             [
                'result' => true,
                'simulations' => $simulationsCallBack
             ]
        );
    }
}
