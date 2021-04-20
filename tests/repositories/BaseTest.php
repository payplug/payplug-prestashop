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

namespace PayPlug\tests\repositories;

use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class BaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $cache;
    protected $carrier;
    protected $cart;
    protected $config;
    protected $constant;
    protected $country;
    protected $logger;
    protected $myLogPhp;

    protected $payplug;

    protected $address;
    protected $context;
    protected $tools;
    protected $translate;
    protected $validate;
    protected $query;

    protected $arrayCache;
    protected $arrayLogger;

    public function setUp()
    {
        $this->cache        = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->carrier      = MockHelper::createMockFactory('Payplug\src\specific\CarrierSpecific');
        $this->cart         = MockHelper::createMockFactory('Payplug\src\specific\CartSpecific');
        $this->config       = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->constant     = MockHelper::createMockFactory('Payplug\src\specific\ConstantSpecific');
        $this->country      = MockHelper::createMockFactory('Payplug\src\specific\CountrySpecific');
        $this->logger       = MockHelper::createMockFactory('Payplug\src\repositories\LoggerRepository');
        $this->query        = MockHelper::createMockFactory('Payplug\src\repositories\QueryRepository');

        $this->myLogPhp     = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        $this->payplug      = \Mockery::mock('payplug');

        $this->address      = MockHelper::createAddressMock('Payplug\src\specific\AddressSpecific');
        $this->context      = MockHelper::createContextMock('Payplug\src\specific\ContextSpecific');
        $this->tools        = MockHelper::createToolsMock('Payplug\src\specific\ToolsSpecific');
        $this->translate    = MockHelper::createTranslateMock('Payplug\src\specific\TranslationSpecific');
        $this->validate     = MockHelper::createValidateMock('Payplug\src\specific\ValidateSpecific');
    }
}
