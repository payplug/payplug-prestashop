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

namespace PayPlugModule\tests\repositories;

use PayPlugModule\classes\AmountCurrencyClass;
use PayPlugModule\classes\DependenciesClass;
use PayPlugModule\tests\mock\MockHelper;
use PayPlugModule\tests\mock\DependenciesMock;
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
        $this->myLogPhp     = MockHelper::createMockFactory('PayPlugModule\classes\MyLogPHP');
        $this->myLogPhp
            ->shouldReceive('info')
            ->andReturn(true);

        $this->setSpecific();
        $this->setRepository();
        $this->setTemporariesClasses();
    }

    private function setSpecific()
    {
        $this->assign               = MockHelper::createAssignMock('PayPlugModule\src\specific\AssignSpecific');
        $this->address              = MockHelper::createAddressMock('PayPlugModule\src\specific\AddressSpecific');
        $this->carrier              = MockHelper::createMockFactory('PayPlugModule\src\specific\CarrierSpecific');
        $this->cart                 = MockHelper::createMockFactory('PayPlugModule\src\specific\CartSpecific');
        $this->config               = MockHelper::createMockFactory('PayPlugModule\src\specific\ConfigurationSpecific');
        $this->constant             = MockHelper::createMockFactory('PayPlugModule\src\specific\ConstantSpecific');
        $this->context              = MockHelper::createContextMock('PayPlugModule\src\specific\ContextSpecific');
        $this->country              = MockHelper::createMockFactory('PayPlugModule\src\specific\CountrySpecific');
        $this->currency             = MockHelper::createMockFactory('PayPlugModule\src\specific\CurrencySpecific');
        $this->language             = MockHelper::createMockFactory('PayPlugModule\src\specific\LanguageSpecific');
        $this->order_state_specific = MockHelper::createMockFactory('PayPlugModule\src\specific\OrderStateSpecific');
        $this->product              = MockHelper::createMockFactory('PayPlugModule\src\specific\ProductSpecific');
        $this->shop                 = MockHelper::createMockFactory('PayPlugModule\src\specific\ShopSpecific');
        $this->tools                = MockHelper::createToolsMock('PayPlugModule\src\specific\ToolsSpecific');
        $this->translate            = MockHelper::createTranslateMock('PayPlugModule\src\specific\TranslationSpecific');
        $this->validate             = MockHelper::createValidateMock('PayPlugModule\src\specific\ValidateSpecific');
    }

    private function setRepository()
    {
        $this->dependencies         = MockHelper::createMockFactory('PayplugModule\classes\DependenciesClass');
        $this->logger               = MockHelper::createMockFactory('PayPlugModule\src\repositories\LoggerRepository');
        $this->query                = MockHelper::createQueryMock('PayPlugModule\src\repositories\QueryRepository');
        $this->sql                  = MockHelper::createMockFactory('PayPlugModule\src\repositories\SQLtableRepository');
    }

    private function setTemporariesClasses()
    {
        $this->dependencies
            ->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            });
        $this->dependencies
            ->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'oneyMinAmounts':
                        return 'PAYPLUG_ONEY_MIN_AMOUNTS';
                    case 'oneyMaxAmounts':
                        return 'PAYPLUG_ONEY_MAX_AMOUNTS';
                    case 'oneyCustomMinAmounts':
                        return 'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS';
                    case 'oneyCustomMaxAmounts':
                        return 'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS';
                    case 'oneyAllowedCountries':
                        return 'PAYPLUG_ONEY_ALLOWED_COUNTRIES';
                    default:
                        return true;
                }
            });
        $this->dependencies->name = DependenciesMock::get();

        $this->dependencies->amountCurrencyClass   = new AmountCurrencyClass($this->tools, $this->dependencies);
        $this->dependencies->apiClass   = \Mockery::mock('alias:PayplugModule\classes\ApiClass');
        $this->dependencies->paymentClass   = \Mockery::mock('alias:PayplugModule\classes\PaymentClass');
        $this->dependencies->configClass    = \Mockery::mock('alias:PayplugModule\classes\ConfigClass');
    }
}
