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

namespace PayPlug\src\application\dependencies;

use PayPlug\classes\ConfigClass;
use PayPlug\classes\AmountCurrencyClass;
use PayPlug\classes\MyLogPHP;

use PayPlug\src\application\dependencies\BaseClass;

use PayPlug\src\repositories\CacheRepository;
use PayPlug\src\repositories\CardRepository;
use PayPlug\src\repositories\HookRepository;
use PayPlug\src\repositories\InstallRepository;
use PayPlug\src\repositories\LoggerRepository;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\src\repositories\OrderStateRepository;
use PayPlug\src\repositories\PaymentRepository;
use PayPlug\src\repositories\QueryRepository;
use PayPlug\src\repositories\SQLtableRepository;
use PayPlug\src\repositories\TranslationsRepository;


use PayPlug\src\models\entities\CacheEntity;
use PayPlug\src\models\entities\OneyEntity;
use PayPlug\src\models\entities\PaymentEntity;
use PayPlug\src\models\entities\PluginEntity;
use PayPlug\src\models\entities\OrderStateEntity;

use PayPlug\src\specific\AddressSpecific;
use PayPlug\src\specific\AssignSpecific;
use PayPlug\src\specific\CarrierSpecific;
use PayPlug\src\specific\CartSpecific;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\specific\ConstantSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\src\specific\CurrencySpecific;
use PayPlug\src\specific\CustomerSpecific;
use PayPlug\src\specific\LanguageSpecific;
use PayPlug\src\specific\ModuleSpecific;
use PayPlug\src\specific\OrderSpecific;
use PayPlug\src\specific\OrderHistorySpecific;
use PayPlug\src\specific\OrderStateSpecific;
use PayPlug\src\specific\ProductSpecific;
use PayPlug\src\specific\ShopSpecific;
use PayPlug\src\specific\ToolsSpecific;
use PayPlug\src\specific\ValidateSpecific;

class PluginInit extends BaseClass
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
            ->setOrderStateSpecific($this->order_state_specific)
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
