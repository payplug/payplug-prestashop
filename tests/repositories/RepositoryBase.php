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

use PayPlug\classes\AmountCurrencyClass;
use PayPlug\classes\DependenciesClass;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\DependenciesMock;
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class RepositoryBase extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $myLogPhp;
    protected $dependencies;
    protected $repo;

    protected $arrayCache;
    protected $arrayLogger;

    // specific
    protected $assign;
    protected $address;
    protected $carrier;
    protected $cart;
    protected $config;
    protected $constant;
    protected $context;
    protected $country;
    protected $currency;
    protected $language;
    protected $product;
    protected $shop;
    protected $tools;
    protected $translate;
    protected $validate;

    // repository
    protected $cache;
    protected $logger;
    protected $order_state;
    protected $order_state_entity;
    protected $query;
    protected $sql;

    // temporary classes
    //  protected $amountCurrencyClass;
//    protected $amountCurrencyClass_static;
    protected $configClass;

    public function setUp()
    {
        $this->myLogPhp     = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');
        $this->myLogPhp
            ->shouldReceive('info')
            ->andReturn(true);
        $this->dependencies      = \Mockery::mock('dependencies');

        $this->setSpecific();
        $this->setRepository();
        $this->setTemporariesClasses();
    }

    private function setSpecific()
    {
        $this->assign               = MockHelper::createAssignMock('Payplug\src\specific\AssignSpecific');
        $this->address              = MockHelper::createAddressMock('Payplug\src\specific\AddressSpecific');
        $this->carrier              = MockHelper::createMockFactory('Payplug\src\specific\CarrierSpecific');
        $this->cart                 = MockHelper::createMockFactory('Payplug\src\specific\CartSpecific');
        $this->config               = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->constant             = MockHelper::createMockFactory('Payplug\src\specific\ConstantSpecific');
        $this->context              = MockHelper::createContextMock('Payplug\src\specific\ContextSpecific');
        $this->country              = MockHelper::createMockFactory('Payplug\src\specific\CountrySpecific');
        $this->currency             = MockHelper::createMockFactory('Payplug\src\specific\CurrencySpecific');
        $this->language             = MockHelper::createMockFactory('Payplug\src\specific\LanguageSpecific');
        $this->order_state_specific = MockHelper::createMockFactory('Payplug\src\specific\OrderStateSpecific');
        $this->product              = MockHelper::createMockFactory('Payplug\src\specific\ProductSpecific');
        $this->shop                 = MockHelper::createMockFactory('Payplug\src\specific\ShopSpecific');
        $this->tools                = MockHelper::createToolsMock('Payplug\src\specific\ToolsSpecific');
        $this->translate            = MockHelper::createTranslateMock('Payplug\src\specific\TranslationSpecific');
        $this->validate             = MockHelper::createValidateMock('Payplug\src\specific\ValidateSpecific');
    }

    private function setRepository()
    {
        $this->logger               = MockHelper::createMockFactory('Payplug\src\repositories\LoggerRepository');
        $this->query                = MockHelper::createMockFactory('Payplug\src\repositories\QueryRepository');
        $this->sql                  = MockHelper::createMockFactory('Payplug\src\repositories\SQLtableRepository');
    }

    private function setTemporariesClasses()
    {
        $this->dependencies = \Mockery::mock('alias:Payplug\classes\DependenciesClass');
        $this->dependencies->name = DependenciesMock::get();
        $this->dependencies->amountCurrencyClass   = new AmountCurrencyClass($this->tools);
        $this->dependencies->paymentClass   = \Mockery::mock('alias:Payplug\classes\PaymentClass');
        $this->dependencies->configClass    = \Mockery::mock('alias:Payplug\classes\ConfigClass');
    }
}
