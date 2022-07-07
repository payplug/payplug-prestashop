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
        $this->myLogPhp     = MockHelper::createMockFactory('PayPlug\classes\MyLogPHP');
        $this->myLogPhp
            ->shouldReceive('info')
            ->andReturn(true);

        $this->setSpecific();
        $this->setRepository();
        $this->setTemporariesClasses();
    }

    private function setSpecific()
    {
        $this->assign               = MockHelper::createAssignMock('PayPlug\src\specific\AssignSpecific');
        $this->address              = MockHelper::createAddressMock('PayPlug\src\specific\AddressSpecific');
        $this->carrier              = MockHelper::createMockFactory('PayPlug\src\specific\CarrierSpecific');
        $this->cart                 = MockHelper::createMockFactory('PayPlug\src\specific\CartSpecific');
        $this->config               = MockHelper::createMockFactory('PayPlug\src\specific\ConfigurationSpecific');
        $this->constant             = MockHelper::createMockFactory('PayPlug\src\specific\ConstantSpecific');
        $this->context              = MockHelper::createContextMock('PayPlug\src\specific\ContextSpecific');
        $this->country              = MockHelper::createMockFactory('PayPlug\src\specific\CountrySpecific');
        $this->currency             = MockHelper::createMockFactory('PayPlug\src\specific\CurrencySpecific');
        $this->language             = MockHelper::createMockFactory('PayPlug\src\specific\LanguageSpecific');
        $this->order_state_specific = MockHelper::createMockFactory('PayPlug\src\specific\OrderStateSpecific');
        $this->product              = MockHelper::createMockFactory('PayPlug\src\specific\ProductSpecific');
        $this->shop                 = MockHelper::createMockFactory('PayPlug\src\specific\ShopSpecific');
        $this->tools                = MockHelper::createToolsMock('PayPlug\src\specific\ToolsSpecific');
        $this->translate            = MockHelper::createTranslateMock('PayPlug\src\specific\TranslationSpecific');
        $this->validate             = MockHelper::createValidateMock('PayPlug\src\specific\ValidateSpecific');
    }

    private function setRepository()
    {
        $this->dependencies         = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->logger               = MockHelper::createMockFactory('PayPlug\src\repositories\LoggerRepository');
        $this->query                = MockHelper::createQueryMock('PayPlug\src\repositories\QueryRepository');
        $this->sql                  = MockHelper::createMockFactory('PayPlug\src\repositories\SQLtableRepository');
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
        $this->dependencies->apiClass   = \Mockery::mock('alias:PayPlug\classes\ApiClass');
        $this->dependencies->paymentClass   = \Mockery::mock('alias:PayPlug\classes\PaymentClass');
        $this->dependencies->configClass    = \Mockery::mock('alias:PayPlug\classes\ConfigClass');
    }
}
