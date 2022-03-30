<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayLaterModule\src\repositories;

use PayLaterModule\classes\ConfigClass;
use PayLaterModule\classes\AmountCurrencyClass;
use PayLaterModule\classes\MyLogPHP;

use PayLaterModule\src\entities\CacheEntity;
use PayLaterModule\src\entities\OneyEntity;
use PayLaterModule\src\entities\PaymentEntity;
use PayLaterModule\src\entities\PluginEntity;
use PayLaterModule\src\entities\OrderStateEntity;

use PayLaterModule\src\specific\AddressSpecific;
use PayLaterModule\src\specific\AssignSpecific;
use PayLaterModule\src\specific\CarrierSpecific;
use PayLaterModule\src\specific\CartSpecific;
use PayLaterModule\src\specific\ConfigurationSpecific;
use PayLaterModule\src\specific\ConstantSpecific;
use PayLaterModule\src\specific\ContextSpecific;
use PayLaterModule\src\specific\CountrySpecific;
use PayLaterModule\src\specific\CurrencySpecific;
use PayLaterModule\src\specific\CustomerSpecific;
use PayLaterModule\src\specific\LanguageSpecific;
use PayLaterModule\src\specific\ModuleSpecific;
use PayLaterModule\src\specific\OrderSpecific;
use PayLaterModule\src\specific\OrderHistorySpecific;
use PayLaterModule\src\specific\OrderStateSpecific;
use PayLaterModule\src\specific\ProductSpecific;
use PayLaterModule\src\specific\ShopSpecific;
use PayLaterModule\src\specific\ToolsSpecific;
use PayLaterModule\src\specific\ValidateSpecific;

class PluginRepository extends Repository
{
    protected $dependencies;

    // Entities
    private $cacheEntity;
    private $oneyEntity;
    private $paymentEntity;
    private $plugin;
    private $order_state_entity;

    // Repositories & necessary classes
    private $amountCurrencyClass;
    private $apiClass;
    private $cache;
    private $card;
    private $hook;
    private $install;
//    private $installment;
    private $logger;
    private $myLogPhp;
    private $oney;
    private $order_state;
    private $payment;
    private $query;
    private $sql;
    private $translate;

    // Specific classes
    private $address;
    private $assign;
    private $carrier;
    private $cart;
    private $configuration;
    private $constant;
    private $context;
    private $country;
    private $currency;
    private $customer;
    private $language;
    private $module;
    private $order;
    private $order_history;
    private $order_state_specific;
    private $product;
    private $shop;
    private $tools;
    private $validate;
    private $constantSpecific;

    public function __construct($dependencies = null)
    {
        $this->dependencies = $dependencies;
        $this->myLogPhp = new MyLogPHP();

        $this->setEntities();
        $this->setSpecific();
        $this->setRepositories();

        $this->plugin
            ->setApiClass($this->apiClass)
            ->setApiVersion('2019-08-06')
            ->setAddress($this->address)
            ->setAmountCurrencyClass($this->amountCurrencyClass)
            ->setAssign($this->assign)
            ->setCache($this->cache)
            ->setCard($this->card)
            ->setCarrier($this->carrier)
            ->setCart($this->cart)
            ->setConfiguration($this->configuration)
            ->setConstant($this->constant)
            ->setContext($this->context)
            ->setCountry($this->country)
            ->setCurrency($this->currency)
            ->setCustomer($this->customer)
            ->setHook($this->hook)
            ->setInstall($this->install)
//            ->setInstallment($this->installment)
            ->setLanguage($this->language)
            ->setLogger($this->logger)
            ->setModule($this->module)
            ->setPayment($this->payment)
            ->setProduct($this->product)
            ->setOney($this->oney)
            ->setOrder($this->order)
            ->setOrderHistory($this->order_history)
            ->setOrderState($this->order_state)
            ->setQuery($this->query)
            ->setSql($this->sql)
            ->setTools($this->tools)
            ->setTranslate($this->translate)
            ->setValidate($this->validate);
        $this->setEntity($this->plugin);
    }

    private function setEntities()
    {
        $this->cacheEntity = new CacheEntity();
        $this->oneyEntity = new OneyEntity();
        $this->paymentEntity = new PaymentEntity();
        $this->plugin = new PluginEntity();
        $this->order_state_entity = new OrderStateEntity();
    }

    private function setRepositories()
    {
        $this->amountCurrencyClass = new AmountCurrencyClass(
            $this->tools,
            $this->dependencies
        );
        $this->logger = new LoggerRepository($this->dependencies);
        $this->query = new QueryRepository();
        $this->translate = new TranslationsRepository();


        $this->sql = new SQLtableRepository(
            $this->dependencies,
            $this->query
        );
        $this->card = new CardRepository(
            $this->configuration,
            $this->constant,
            $this->dependencies,
            $this->logger,
            $this->query,
            $this->tools
        );

        $this->sql = new SQLtableRepository(
            $this->dependencies,
            $this->query
        );

        $this->hook = new HookRepository(
            $this->dependencies,
            $this->constant,
            $this->context,
            $this->tools
        );

        $this->cache    = new CacheRepository(
            $this->cacheEntity,
            $this->query,
            $this->configuration,
            $this->dependencies,
            $this->logger,
            $this->constant
        );

        $this->oney = new OneyRepository(
            $this->address,
            $this->assign,
            $this->cache,
            $this->carrier,
            $this->cart,
            $this->configuration,
            $this->context,
            $this->country,
            $this->currency,
            $this->dependencies,
            $this->logger,
            $this->myLogPhp,
            $this->oneyEntity,
            $this->tools,
            $this->validate
        );

        $this->order_state = new OrderStateRepository(
            $this->configuration,
            $this->constant,
            $this->dependencies,
            $this->language,
            $this->order_state_specific,
            $this->query,
            $this->tools,
            $this->validate,
            $this->myLogPhp
        );

        $this->payment = new PaymentRepository(
            $this->cart,
            $this->configuration,
            $this->dependencies,
            $this->logger,
            $this->paymentEntity,
            $this->query,
            $this->constant
        );

        $this->install = new InstallRepository(
            $this->configuration,
            $this->constant,
            $this->context,
            $this->dependencies,
            $this->order_state,
            $this->order_state_entity,
            $this->order_state_specific,
            $this->shop,
            $this->sql,
            $this->tools,
            $this->validate,
            $this->myLogPhp
        );
    }

    private function setSpecific()
    {
        $this->address = new AddressSpecific();
        $this->assign = new AssignSpecific();
        $this->carrier = new CarrierSpecific();
        $this->cart = new CartSpecific();
        $this->configuration = new ConfigurationSpecific();
        $this->constant = new ConstantSpecific();
        $this->context = new ContextSpecific();
        $this->country = new CountrySpecific();
        $this->currency  = new CurrencySpecific();
        $this->customer  = new CustomerSpecific();
        $this->language = new LanguageSpecific();
        $this->module = new ModuleSpecific();
        $this->order = new OrderSpecific();
        $this->order_history = new OrderHistorySpecific();
        $this->order_state_specific = new OrderStateSpecific();
        $this->product = new ProductSpecific();
        $this->shop = new ShopSpecific();
        $this->tools = new ToolsSpecific();
        $this->validate = new ValidateSpecific();
    }
}
