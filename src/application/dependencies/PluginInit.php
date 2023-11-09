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

use PayPlug\classes\MyLogPHP;
use PayPlug\src\actions\CardAction;
use PayPlug\src\actions\ConfigurationAction;
use PayPlug\src\actions\MerchantTelemetryAction;
use PayPlug\src\actions\OnboardingAction;
use PayPlug\src\actions\OrderStateAction;
use PayPlug\src\actions\PaymentAction;
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
use PayPlug\src\application\adapter\QueryAdapter;
use PayPlug\src\application\adapter\ShopAdapter;
use PayPlug\src\application\adapter\ToolsAdapter;
use PayPlug\src\application\adapter\ValidateAdapter;
use PayPlug\src\models\classes\ApiRest;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\models\entities\CacheEntity;
use PayPlug\src\models\entities\OneyEntity;
use PayPlug\src\models\entities\OrderStateEntity;
use PayPlug\src\models\entities\PaymentEntity;
use PayPlug\src\models\entities\PluginEntity;
use PayPlug\src\repositories\CacheRepository;
use PayPlug\src\repositories\InstallRepository;
use PayPlug\src\repositories\LoggerRepository;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\src\repositories\OrderStateRepository;
use PayPlug\src\repositories\PaymentRepository;
use PayPlug\src\repositories\SQLtableRepository;
use PayPlug\src\repositories\TranslationsRepository;
use PayPlug\src\utilities\services\Browser;
use PayPlug\src\utilities\services\MerchantTelemetry;
use PayPlug\src\utilities\services\Routes;

class PluginInit extends BaseClass
{
    protected $dependencies;

    // Actions
    private $card_action;
    private $configuration_action;
    private $onboarding_action;
    private $order_state_action;
    private $merchant_telemetry_action;
    private $paymentAction;

    // EntitiesApiRest
    private $cacheEntity;
    private $oneyEntity;
    private $paymentEntity;
    private $plugin;
    private $order_state_entity;

    // Repositories & necessary classes
    private $apiClass;
    private $cache;
    private $install;
    private $logger;
    private $myLogPhp;
    private $oney;
    private $order_state;
    private $payment;
    private $sql;
    private $translate;

    // Adapter classes
    private $address_adapter;
    private $assign_adapter;
    private $carrier_adapter;
    private $cart_adapter;
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
    private $tools_adapter;
    private $validate_adapter;

    // Model classes
    private $api_rest_class;
    private $configuration_class;
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
            ->setBrowser($this->browser)
            ->setCache($this->cache)
            ->setMerchantTelemetry($this->merchant_telemetry)
            ->setInstall($this->install)
            ->setLogger($this->logger)
            ->setPayment($this->payment)
            ->setOney($this->oney)
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
            ->setTools($this->tools_adapter)
            ->setValidate($this->validate_adapter)
        ;

        // Set actions
        $this->plugin
            ->setCardAction($this->card_action)
            ->setConfigurationAction($this->configuration_action)
            ->setMerchantTelemetryAction($this->merchant_telemetry_action)
            ->setOnboardingAction($this->onboarding_action)
            ->setOrderStateAction($this->order_state_action)
            ->setPaymentAction($this->paymentAction)
        ;

        // Set models/classes
        $this->plugin
            ->setApiRestClass($this->api_rest_class)
            ->setConfigurationClass($this->configuration_class)
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
            ->setPayplugOrderStateRepository($this->payplug_order_state_repository)
            ->setQueryRepository($this->query_repository)
            ->setShopRepository($this->shop_repository)
        ;

        $this->setEntity($this->plugin);
    }

    private function setActions()
    {
        $this->card_action = new CardAction($this->dependencies);
        $this->configuration_action = new ConfigurationAction($this->dependencies);
        $this->merchant_telemetry_action = new MerchantTelemetryAction($this->dependencies);
        $this->onboarding_action = new OnboardingAction($this->dependencies);
        $this->order_state_action = new OrderStateAction($this->dependencies);
        $this->paymentAction = new PaymentAction($this->dependencies);
    }

    private function setEntities()
    {
        $this->cacheEntity = new CacheEntity();
        $this->oneyEntity = new OneyEntity();
        $this->paymentEntity = new PaymentEntity();
        $this->plugin = new PluginEntity();
        $this->order_state_entity = new OrderStateEntity();
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

        $this->oney = new OneyRepository(
            $this->address_adapter,
            $this->assign_adapter,
            $this->cache,
            $this->carrier_adapter,
            $this->cart_adapter,
            $this->configuration_adapter,
            $this->context_adapter,
            $this->country_adapter,
            $this->currency_adapter,
            $this->media_adapter,
            $this->dependencies,
            $this->logger,
            $this->oneyEntity,
            $this->tools_adapter,
            $this->validate_adapter
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

        $this->payment = new PaymentRepository(
            $this->cart_adapter,
            $this->configuration_adapter,
            $this->configuration_class,
            $this->constant_adapter,
            $this->dependencies,
            $this->logger,
            $this->paymentEntity,
            $this->query_repository
        );

        $this->install = new InstallRepository(
            $this->configuration_class,
            $this->constant_adapter,
            $this->context_adapter,
            $this->dependencies,
            $this->order_state,
            $this->order_state_entity,
            $this->order_state_adapter,
            $this->query_repository,
            $this->shop_adapter,
            $this->sql,
            $this->tools_adapter,
            $this->validate_adapter,
            $this->myLogPhp
        );
    }

    private function setAdapter()
    {
        $this->address_adapter = new AddressAdapter();
        $this->assign_adapter = new AssignAdapter();
        $this->carrier_adapter = new CarrierAdapter();
        $this->cart_adapter = new CartAdapter();
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
        $this->tools_adapter = new ToolsAdapter();
        $this->validate_adapter = new ValidateAdapter();
    }

    private function setClasses()
    {
        $this->api_rest_class = new ApiRest($this->dependencies);
        $this->configuration_class = new Configuration($this->dependencies);
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
        $this->payplug_order_state_repository = new \PayPlug\src\models\repositories\PayplugOrderStateRepository($prefix, $this->dependencies);
        $this->query_repository = new \PayPlug\src\models\repositories\QueryRepository($prefix, $this->dependencies);
        $this->shop_repository = new \PayPlug\src\models\repositories\ShopRepository($prefix, $this->dependencies);
    }

    private function setServices()
    {
        $this->browser = new Browser();
        $this->routes = new Routes();
        $this->merchant_telemetry = new MerchantTelemetry();
    }
}
