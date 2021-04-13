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

namespace PayPlug\tests\repositories\CacheRepository;

/**
 * @group dev
 * @group unit
 * @group repository
 * @group cache
 * @group cache_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetCacheByKeyTest extends BaseCacheRepository
{
    private $cacheKey;

    public function setUp()
    {
        parent::setUp();
        $this->cacheKey = 'Payplug::OneySimulations_15000_FR_operation_live';
    }

    public function invalidDataProvider()
    {
        yield [false];
        yield [[]];
        yield [42];
        yield [''];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testWithInvalidDataProvider($cacheKey)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid cache key format'
            ],
            $this->repo->getCacheByKey($cacheKey)
        );
    }
}
