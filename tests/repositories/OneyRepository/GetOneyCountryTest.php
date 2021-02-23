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

use PayPlug\src\repositories\OneyRepository;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyCountryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // Default setup
    protected $cache;
    protected $logger;
    protected $config;
    protected $myLogPhp;

    public function setUp()
    {
        // Default setup for Oney Repository using
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->logger = MockHelper::createMockFactory('Payplug\src\repositories\LoggerRepository');
        $this->config = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->myLogPhp = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        // Method Params
        $this->payplug = Mockery::mock('payplug');
        $this->repo = new OneyRepository();
        $this->repo->setPayplug($this->payplug);
    }

    public function testWithEmptyIsoCode()
    {
        $this->assertSame(
            false,
            $this->repo->getOneyCountry(null)
        );
    }

    public function testWithWrongIsoCode()
    {
        $this->assertSame(
            false,
            $this->repo->getOneyCountry(42)
        );
    }

    public function testGetDefaultIsoCode()
    {
        $overseas_iso = ['GP', 'MQ', 'GF', 'RE', 'YT'];

        foreach($overseas_iso as $iso) {
            $this->assertSame(
                'FR',
                $this->repo->getOneyCountry($iso)
            );
        }
    }

    public function testGetCustomIsoCode()
    {
        $iso_code = 'BE';
        $this->assertSame(
            $iso_code,
            $this->repo->getOneyCountry($iso_code)
        );
    }
}
