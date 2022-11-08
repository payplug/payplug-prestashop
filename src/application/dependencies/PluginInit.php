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

use PayPlug\classes\MyLogPHP;
use PayPlug\src\application\adapter\AddressAdapter;
use PayPlug\src\application\adapter\AssignAdapter;
use PayPlug\src\application\adapter\CarrierAdapter;
use PayPlug\src\application\adapter\CartAdapter;
use PayPlug\src\application\adapter\ConfigurationAdapter;
use PayPlug\src\application\adapter\ConstantAdapter;
use PayPlug\src\application\adapter\ContextAdapter;
use PayPlug\src\application\adapter\CountryAdapter;
use PayPlug\src\application\adapter\CurrencyAdapter;
use PayPlug\src\application\adapter\CustomerAdapter;
use PayPlug\src\application\adapter\DispatcherAdapter;
use PayPlug\src\application\adapter\LanguageAdapter;
use PayPlug\src\application\adapter\MediaAdapter;
use PayPlug\src\application\adapter\MessageAdapter;
use PayPlug\src\application\adapter\ModuleAdapter;
use PayPlug\src\application\adapter\OrderAdapter;
use PayPlug\src\application\adapter\OrderHistoryAdapter;
use PayPlug\src\application\adapter\OrderSlipAdapter;
use PayPlug\src\application\adapter\OrderStateAdapter;
use PayPlug\src\application\adapter\ProductAdapter;
use PayPlug\src\application\adapter\ShopAdapter;
use PayPlug\src\application\adapter\ToolsAdapter;
use PayPlug\src\application\adapter\ValidateAdapter;
use PayPlug\src\models\entities\CacheEntity;
use PayPlug\src\models\entities\OneyEntity;
use PayPlug\src\models\entities\OrderStateEntity;
use PayPlug\src\models\entities\PaymentEntity;
use PayPlug\src\models\entities\PluginEntity;
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
    private $apiClass;
    private $cache;
    private $card;
    private $hook;
    private $install;
    private $logger;
    private $myLogPhp;
    private $oney;
    private $order_state;
    private $payment;
    private $query;
    private $sql;
    private $translate;

    // Adapter classes
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
    private $dispatcher;
    private $language;
    private $media;
    private $message;
    private $module;
    private $order;
    private $order_history;
    private $order_slip;
    private $order_state_adapter;
    private $product;
    private $shop;
    private $tools;
    private $validate;

    public function __construct($dependencies = null)
    {
        $this->dependencies = $dependencies;
        $this->myLogPhp = new MyLogPHP();

        $this->setEntities();
        $this->setAdapter();
        $this->setRepositories();

        $this->plugin
            ->setApiClass($this->apiClass)
            ->setApiVersion('2019-08-06')
            ->setAddress($this->address)
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
            ->setDispatcher($this->dispatcher)
            ->setHook($this->hook)
            ->setInstall($this->install)
            ->setLanguage($this->language)
            ->setLogger($this->logger)
            ->setMedia($this->media)
            ->setMessage($this->message)
            ->setModule($this->module)
            ->setPayment($this->payment)
            ->setProduct($this->product)
            ->setOney($this->oney)
            ->setOrder($this->order)
            ->setOrderHistory($this->order_history)
            ->setOrderState($this->order_state)
            ->setOrderSlip($this->order_slip)
            ->setOrderStateAdapter($this->order_state_adapter)
            ->setQuery($this->query)
            ->setSql($this->sql)
            ->setShop($this->shop)
            ->setTools($this->tools)
            ->setTranslate($this->translate)
            ->setValidate($this->validate)
        ;

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

        $this->cache = new CacheRepository(
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
            $this->order_state_adapter,
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
            $this->order_state_adapter,
            $this->shop,
            $this->sql,
            $this->tools,
            $this->validate,
            $this->myLogPhp
        );
    }

    private function setAdapter()
    {
        $this->address = new AddressAdapter();
        $this->assign = new AssignAdapter();
        $this->carrier = new CarrierAdapter();
        $this->cart = new CartAdapter();
        $this->configuration = new ConfigurationAdapter();
        $this->constant = new ConstantAdapter();
        $this->context = new ContextAdapter();
        $this->country = new CountryAdapter();
        $this->currency = new CurrencyAdapter();
        $this->customer = new CustomerAdapter();
        $this->dispatcher = new DispatcherAdapter();
        $this->language = new LanguageAdapter();
        $this->media = new MediaAdapter();
        $this->message = new MessageAdapter();
        $this->module = new ModuleAdapter();
        $this->order = new OrderAdapter();
        $this->order_history = new OrderHistoryAdapter();
        $this->order_slip = new OrderSlipAdapter();
        $this->order_state_adapter = new OrderStateAdapter();
        $this->product = new ProductAdapter();
        $this->shop = new ShopAdapter();
        $this->tools = new ToolsAdapter();
        $this->validate = new ValidateAdapter();
    }
}
