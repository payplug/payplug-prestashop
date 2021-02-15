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
use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\CarrierMock;
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
final class GetOneyDeliveryContextTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // Default setup
    protected $cache;
    protected $logger;
    protected $config;
    protected $myLogPhp;

    // Method setup
    protected $cart;
    protected $carrier;
    protected $context;

    public function setUp()
    {
        // Default setup for Oney Repository using
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->logger = MockHelper::createMockFactory('Payplug\src\repositories\LoggerRepository');
        $this->config = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->myLogPhp = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        // Method setup
        $this->cart = MockHelper::createContextMock('Payplug\src\specific\CartSpecific');
        $this->carrier = MockHelper::createContextMock('Payplug\src\specific\CarrierSpecific');
        $this->carrier->carrier_default_delay = 0;
        $this->carrier->delivery_type = 'storepickup';

        $this->context = MockHelper::createContextMock('Payplug\src\specific\ContextSpecific');

        // Method Params
        $this->payplug = Mockery::mock('payplug');
        $this->repo = new OneyRepository($this->payplug);
    }


    public function testGetContextFromVirtual()
    {
        $this->cart->shouldReceive('get')
            ->andReturn(CartMock::get());
        $this->cart->shouldReceive('isVirtualCart')
            ->andReturn(true);

        $this->config->shouldReceive('get')
            ->with('PS_SHOP_NAME')
            ->andReturn('Payplug');

        $this->assertSame(
            [
                'delivery_label' => 'Payplug',
                'expected_delivery_date' => date('Y-m-d'),
                'delivery_type' => 'edelivery',
            ],
            $this->repo->getOneyDeliveryContext()
        );
    }


    public function testGetContext()
    {
        $this->cart->shouldReceive('get')
            ->andReturn(CartMock::get());
        $this->cart->shouldReceive('isVirtualCart')
            ->andReturn(false);

        $this->carrier->shouldReceive('get')
            ->andReturn(CarrierMock::get());
        $this->carrier->shouldReceive('getDefaultDelay')
            ->andReturn(0);
        $this->carrier->shouldReceive('getDefaultDeliveryType')
            ->andReturn('storepickup');

        $this->config->shouldReceive('get')
            ->with('PS_SHOP_NAME')
            ->andReturn('Payplug');

        $this->assertSame(
            [
                'delivery_label' => 'Carrier name',
                'expected_delivery_date' => date('Y-m-d'),
                'delivery_type' => 'storepickup',
            ],
            $this->repo->getOneyDeliveryContext()
        );
    }
}
