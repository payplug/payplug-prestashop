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

use PayPlug\tests\mock\MockHelper;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group unit
 * @group repository
 * @group cache
 * @group cache_repository
 *
 * @runTestsInSeparateProcesses
 */
final class setCacheKeyTest extends BaseCacheRepository
{
    use MockeryPHPUnitIntegration;

    protected $query;
    protected $config;

    protected $repo;

    public function invalidDataProvider()
    {
        yield [15000, 'FR', 'not a array', 'Operations is not a valid int'];
        yield ['not numeric', 'FR', ['operation'], 'Amount is not a valid string'];
        yield [15000, false, ['operation'], 'Country is not a valid array'];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testWithInvalidDataProvider($amount, $country, $operations, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg
            ],
            $this->repo->setCacheKey($amount, $country, $operations)
        );
    }

    public function testWithValidData()
    {
        $this->config->shouldReceive([
            'get' => false
        ]);
        $this->assertSame(
            'Payplug::OneySimulations_15000_FR_operation_live',
            $this->repo->setCacheKey(15000, 'FR', ['operation'])
        );
    }
}
