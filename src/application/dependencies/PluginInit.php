<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\application\dependencies;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\MyLogPHP;
use PayPlug\src\actions\CardAction;
use PayPlug\src\actions\CartAction;
use PayPlug\src\actions\ConfigurationAction;
use PayPlug\src\actions\MerchantTelemetryAction;
use PayPlug\src\actions\OnboardingAction;
use PayPlug\src\actions\OneyAction;
use PayPlug\src\actions\OrderAction;
use PayPlug\src\actions\OrderStateAction;
use PayPlug\src\actions\PaymentAction;
use PayPlug\src\actions\RefundAction;
use PayPlug\src\application\adapter\AddressAdapter;
use PayPlug\src\application\adapter\AssignAdapter;
use PayPlug\src\application\adapter\CarrierAdapter;
use PayPlug\src\application\adapter\CartAdapter;
use PayPlug\src\application\adapter\CartRuleAdapter;
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
use PayPlug\src\application\adapter\QueryAdapter;
use PayPlug\src\application\adapter\ShopAdapter;
use PayPlug\src\application\adapter\TabAdapter;
use PayPlug\src\application\adapter\ToolsAdapter;
use PayPlug\src\application\adapter\TranslationAdapter;
use PayPlug\src\application\adapter\ValidateAdapter;
use PayPlug\src\models\classes\Address;
use PayPlug\src\models\classes\ApiRest;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\Country;
use PayPlug\src\models\classes\Order;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\models\entities\CacheEntity;
use PayPlug\src\models\entities\PluginEntity;
use PayPlug\src\repositories\CacheRepository;
use PayPlug\src\repositories\InstallRepository;
use PayPlug\src\repositories\LoggerRepository;
use PayPlug\src\repositories\OrderStateRepository;
use PayPlug\src\repositories\SQLtableRepository;
use PayPlug\src\repositories\TranslationsRepository;
use PayPlug\src\utilities\services\API;
use PayPlug\src\utilities\services\Browser;
use PayPlug\src\utilities\services\MerchantTelemetry;
use PayPlug\src\utilities\services\Routes;

class PluginInit extends BaseClass
{
    protected $dependencies;

    // Actions
    private $card_action;
    private $cart_action;
    private $configuration_action;
    private $onboarding_action;
    private $oney_action;
    private $order_action;
    private $refund_action;
    private $order_state_action;
    private $merchant_telemetry_action;
    private $paymentAction;

    // EntitiesApiRest
    private $cacheEntity;
    private $oneyEntity;
    private $plugin;
    private $order_state_entity;

    // Repositories & necessary classes
    private $apiClass;
    private $cache;
    private $install;
    private $logger;
    private $myLogPhp;
    private $order_state;
    private $sql;
    private $translate;

    // Adapter classes
    private $address_adapter;
    private $assign_adapter;
    private $carrier_adapter;
    private $cart_adapter;
    private $cart_rule_adapter;
    private $configuration_adapter;
    private $constant_adapter;
    private $context_adapter;
    private $country_adapter;
    private $currency_adapter;
    private $customer_adapter;
    private $dispatcher_adapter;
    private $language_adapter;
    private $media_adapter;
    private $message_adapter;
    private $module_adapter;
    private $order_adapter;
    private $order_history_adapter;
    private $order_slip_adapter;
    private $order_state_adapter;
    private $product_adapter;
    private $query_adapter;
    private $shop_adapter;
    private $tab_adapter;
    private $tools_adapter;
    private $translation_adapter;
    private $validate_adapter;

    // Model classes
    private $address_class;
    private $api_rest_class;
    private $country_class;
    private $configuration_class;
    private $order_class;
    private $payment_method_class;
    private $translation_class;

    // Model repositories
    private $card_repository;
    private $cache_repository;
    private $country_repository;
    private $lock_repository;
    private $logger_repository;
    private $module_repository;
    private $order_repository;
    private $order_state_repository;
    private $order_payment_repository;
    private $payment_repository;
    private $payplug_order_state_repository;
    private $query_repository;
    private $shop_repository;

    // Utilities services
    private $api;
    private $browser;
    private $routes;
    private $merchant_telemetry;

    public function __construct($dependencies = null)
    {
        $this->dependencies = $dependencies;

        $this->setActions();
        $this->setEntities();
        $this->setAdapter();
        $this->setClasses();
        $this->setRepositories();
        $this->setOldRepositories();
        $this->setServices();

        $this->plugin
            ->setApiClass($this->apiClass)
            ->setApiVersion('2019-08-06')
            ->setApiService($this->api)
            ->setBrowser($this->browser)
            ->setCache($this->cache)
            ->setMerchantTelemetry($this->merchant_telemetry)
            ->setInstall($this->install)
            ->setLogger($this->logger)
            ->setOrderState($this->order_state)
            ->setSql($this->sql)
            ->setRoutes($this->routes)
            ->setTranslate($this->translate)
        ;

        // Set application/adapter
        $this->plugin
            ->setAddress($this->address_adapter)
            ->setAssign($this->assign_adapter)
            ->setCarrier($this->carrier_adapter)
            ->setCart($this->cart_adapter)
            ->setCartRule($this->cart_rule_adapter)
            ->setConfiguration($this->configuration_adapter)
            ->setConstant($this->constant_adapter)
            ->setContext($this->context_adapter)
            ->setCountry($this->country_adapter)
            ->setCurrency($this->currency_adapter)
            ->setCustomer($this->customer_adapter)
            ->setDispatcher($this->dispatcher_adapter)
            ->setLanguage($this->language_adapter)
            ->setMedia($this->media_adapter)
            ->setMessage($this->message_adapter)
            ->setModule($this->module_adapter)
            ->setOrder($this->order_adapter)
            ->setOrderHistory($this->order_history_adapter)
            ->setOrderSlip($this->order_slip_adapter)
            ->setOrderStateAdapter($this->order_state_adapter)
            ->setProduct($this->product_adapter)
            ->setQueryAdapter($this->query_adapter)
            ->setShop($this->shop_adapter)
            ->setTabAdapter($this->tab_adapter)
            ->setTools($this->tools_adapter)
            ->setTranslationAdapter($this->translation_adapter)
            ->setValidate($this->validate_adapter)
        ;

        // Set actions
        $this->plugin
            ->setCardAction($this->card_action)
            ->setCartAction($this->cart_action)
            ->setConfigurationAction($this->configuration_action)
            ->setMerchantTelemetryAction($this->merchant_telemetry_action)
            ->setOnboardingAction($this->onboarding_action)
            ->setOneyAction($this->oney_action)
            ->setOrderAction($this->order_action)
            ->setRefundAction($this->refund_action)
            ->setOrderStateAction($this->order_state_action)
            ->setPaymentAction($this->paymentAction)
        ;

        // Set models/classes
        $this->plugin
            ->setAddressClass($this->address_class)
            ->setApiRestClass($this->api_rest_class)
            ->setConfigurationClass($this->configuration_class)
            ->setCountryClass($this->country_class)
            ->setOrderClass($this->order_class)
            ->setPaymentMethodClass($this->payment_method_class)
            ->setTranslationClass($this->translation_class)
        ;

        // Set models/repositories
        $this->plugin
            ->setCardRepository($this->card_repository)
            ->setCacheRepository($this->cache_repository)
            ->setCountryRepository($this->country_repository)
            ->setLockRepository($this->lock_repository)
            ->setLoggerRepository($this->logger_repository)
            ->setModuleRepository($this->module_repository)
            ->setOrderRepository($this->order_repository)
            ->setOrderStateRepository($this->order_state_repository)
            ->setOrderPaymentRepository($this->order_payment_repository)
            ->setPaymentRepository($this->payment_repository)
            ->setStateRepository($this->payplug_order_state_repository)
            ->setQueryRepository($this->query_repository)
            ->setShopRepository($this->shop_repository)
        ;

        $this->setEntity($this->plugin);
    }

    private function setActions()
    {
        $this->card_action = new CardAction($this->dependencies);
        $this->cart_action = new CartAction($this->dependencies);
        $this->configuration_action = new ConfigurationAction($this->dependencies);
        $this->merchant_telemetry_action = new MerchantTelemetryAction($this->dependencies);
        $this->onboarding_action = new OnboardingAction($this->dependencies);
        $this->oney_action = new OneyAction($this->dependencies);
        $this->order_action = new OrderAction($this->dependencies);
        $this->refund_action = new RefundAction($this->dependencies);
        $this->order_state_action = new OrderStateAction($this->dependencies);
        $this->paymentAction = new PaymentAction($this->dependencies);
    }

    private function setEntities()
    {
        $this->cacheEntity = new CacheEntity();
        $this->plugin = new PluginEntity();
    }

    private function setOldRepositories()
    {
        $module_dir = $this->constant_adapter->get('_PS_MODULE_DIR_');
        $this->myLogPhp = new MyLogPHP($module_dir . $this->dependencies->name . '/log/install-log.csv');

        $this->logger = new LoggerRepository($this->dependencies);
        $this->translate = new TranslationsRepository();

        $this->sql = new SQLtableRepository(
            $this->dependencies,
            $this->query_repository
        );

        $this->sql = new SQLtableRepository(
            $this->dependencies,
            $this->query_repository
        );

        $this->cache = new CacheRepository(
            $this->cacheEntity,
            $this->query_repository,
            $this->configuration_class,
            $this->dependencies,
            $this->logger,
            $this->constant_adapter
        );

        $this->order_state = new OrderStateRepository(
            $this->configuration_class,
            $this->constant_adapter,
            $this->dependencies,
            $this->language_adapter,
            $this->order_state_adapter,
            $this->query_repository,
            $this->tools_adapter,
            $this->validate_adapter,
            $this->myLogPhp
        );

        $this->install = new InstallRepository(
            $this->dependencies
        );
    }

    private function setAdapter()
    {
        $this->address_adapter = new AddressAdapter();
        $this->assign_adapter = new AssignAdapter();
        $this->carrier_adapter = new CarrierAdapter();
        $this->cart_adapter = new CartAdapter();
        $this->cart_rule_adapter = new CartRuleAdapter();
        $this->configuration_adapter = new ConfigurationAdapter();
        $this->constant_adapter = new ConstantAdapter();
        $this->context_adapter = new ContextAdapter();
        $this->country_adapter = new CountryAdapter();
        $this->currency_adapter = new CurrencyAdapter();
        $this->customer_adapter = new CustomerAdapter();
        $this->dispatcher_adapter = new DispatcherAdapter();
        $this->language_adapter = new LanguageAdapter();
        $this->media_adapter = new MediaAdapter();
        $this->message_adapter = new MessageAdapter();
        $this->module_adapter = new ModuleAdapter();
        $this->order_adapter = new OrderAdapter();
        $this->order_history_adapter = new OrderHistoryAdapter();
        $this->order_slip_adapter = new OrderSlipAdapter();
        $this->order_state_adapter = new OrderStateAdapter();
        $this->product_adapter = new ProductAdapter();
        $this->query_adapter = new QueryAdapter();
        $this->shop_adapter = new ShopAdapter();
        $this->tab_adapter = new TabAdapter();
        $this->tools_adapter = new ToolsAdapter();
        $this->translation_adapter = new TranslationAdapter();
        $this->validate_adapter = new ValidateAdapter();
    }

    private function setClasses()
    {
        $this->address_class = new Address($this->dependencies);
        $this->api_rest_class = new ApiRest($this->dependencies);
        $this->configuration_class = new Configuration($this->dependencies);
        $this->country_class = new Country($this->dependencies);
        $this->order_class = new Order($this->dependencies);
        $this->payment_method_class = new PaymentMethod($this->dependencies);
        $this->translation_class = new Translation($this->dependencies);
    }

    private function setRepositories()
    {
        $prefix = $this->constant_adapter->get('_DB_PREFIX_');

        // We use complete path instead `use` to avoid confusion with old repositories
        $this->card_repository = new \PayPlug\src\models\repositories\CardRepository($prefix, $this->dependencies);
        $this->cache_repository = new \PayPlug\src\models\repositories\CacheRepository($prefix, $this->dependencies);
        $this->country_repository = new \PayPlug\src\models\repositories\CountryRepository($prefix, $this->dependencies);
        $this->lock_repository = new \PayPlug\src\models\repositories\LockRepository($prefix, $this->dependencies);
        $this->logger_repository = new \PayPlug\src\models\repositories\LoggerRepository($prefix, $this->dependencies);
        $this->module_repository = new \PayPlug\src\models\repositories\ModuleRepository($prefix, $this->dependencies);
        $this->order_repository = new \PayPlug\src\models\repositories\OrderRepository($prefix, $this->dependencies);
        $this->order_state_repository = new \PayPlug\src\models\repositories\OrderStateRepository($prefix, $this->dependencies);
        $this->order_payment_repository = new \PayPlug\src\models\repositories\OrderPaymentRepository($prefix, $this->dependencies);
        $this->payment_repository = new \PayPlug\src\models\repositories\PaymentRepository($prefix, $this->dependencies);
        $this->payplug_order_state_repository = new \PayPlug\src\models\repositories\StateRepository($prefix, $this->dependencies);
        $this->query_repository = new \PayPlug\src\models\repositories\QueryRepository($prefix, $this->dependencies);
        $this->shop_repository = new \PayPlug\src\models\repositories\ShopRepository($prefix, $this->dependencies);
    }

    private function setServices()
    {
        $this->api = new API($this->dependencies);
        $this->browser = new Browser();
        $this->routes = new Routes();
        $this->merchant_telemetry = new MerchantTelemetry();
    }
}
