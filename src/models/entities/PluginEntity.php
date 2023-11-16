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

namespace PayPlug\src\models\entities;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\exceptions\BadParameterException;

class PluginEntity
{
    /** @var object */
    private $address;

    /** @var object */
    private $apiClass;

    /** @var object */
    private $api_rest;

    /** @var string */
    private $api_version;

    /** @var object */
    private $assign;

    /** @var object */
    private $browser;

    /** @var object */
    private $cache;

    /** @var object */
    private $card_action;

    /** @var object */
    private $cart;

    /** @var object */
    private $carrier;

    /** @var object */
    private $configuration;

    /** @var object */
    private $configuration_action;

    /** @var object */
    private $configuration_class;

    /** @var object */
    private $country_class;

    /** @var object */
    private $constant;

    /** @var object */
    private $context;

    /** @var object */
    private $country;

    /** @var object */
    private $currency;

    /** @var object */
    private $customer;

    /** @var object */
    private $dispatcher;

    /** @var object */
    private $install;

    /** @var object */
    private $language;

    /** @var object */
    private $logger;

    /** @var object */
    private $merchantTelemetry_action;

    /** @var object */
    private $message;

    /** @var object */
    private $myLogPHP;

    /** @var object */
    private $media;

    /** @var object */
    private $module;

    /** @var object */
    private $onboarding_action;

    /** @var object */
    private $oney;

    /** @var object */
    private $order;

    /** @var object */
    private $orderHistory;

    /** @var object */
    private $order_slip;

    /** @var object */
    private $order_state;

    /** @var object */
    private $order_state_action;

    /** @var object */
    private $order_state_adapter;

    /** @var object */
    private $payment_action;

    /** @var object */
    private $product;

    /** @var object */
    private $query_repository;

    /** @var object */
    private $query_adapter;

    /** @var object */
    private $routes;

    /** @var object */
    private $shop;

    /** @var object */
    private $sql;

    /** @var object */
    private $tools;

    /** @var object */
    private $translation;

    /** @var object */
    private $translate;

    /** @var object */
    private $validate;

    /** @var object */
    private $card_repository;

    /** @var object */
    private $cache_repository;

    /** @var object */
    private $country_repository;

    /** @var object */
    private $lock_repository;

    /** @var object */
    private $logger_repository;

    /** @var object */
    private $module_repository;

    /** @var object */
    private $order_repository;

    /** @var object */
    private $order_state_repository;

    /** @var object */
    private $payplug_order_state_repository;

    /** @var object */
    private $order_payment_repository;

    /** @var object */
    private $payment_repository;

    /** @var object */
    private $shop_repository;

    /** @var object */
    private $translation_adapter;

    /**
     * @return object
     */
    public function getApiClass()
    {
        return $this->apiClass;
    }

    /**
     * @return object
     */
    public function getMyLogPHP()
    {
        return $this->myLogPHP;
    }

    /**
     * @return object
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @return object
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return object
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param object $myLogPHP
     *
     * @return PluginEntity
     */
    public function setMyLogPHP($myLogPHP)
    {
        $this->myLogPHP = $myLogPHP;

        return $this;
    }

    /**
     * @return object
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param object $order
     *
     * @return PluginEntity
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return object
     */
    public function getOrderHistory()
    {
        return $this->orderHistory;
    }

    /**
     * @param object $orderHistory
     *
     * @return PluginEntity
     */
    public function setOrderHistory($orderHistory)
    {
        $this->orderHistory = $orderHistory;

        return $this;
    }

    /**
     * @return object
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return object
     */
    public function getApiRestClass()
    {
        return $this->api_rest;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * @return string
     */
    public function getAssign()
    {
        return $this->assign;
    }

    /**
     * @return object
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @return object
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return object
     */
    public function getCardRepository()
    {
        return $this->card_repository;
    }

    /**
     * @return object
     */
    public function getCacheRepository()
    {
        return $this->cache_repository;
    }

    /**
     * @return object
     */
    public function getCardAction()
    {
        return $this->card_action;
    }

    /**
     * @return object
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @return object
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return object
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return object
     */
    public function getConfigurationAction()
    {
        return $this->configuration_action;
    }

    /**
     * @return object
     */
    public function getConfigurationClass()
    {
        return $this->configuration_class;
    }

    /**
     * @return object
     */
    public function getCountryClass()
    {
        return $this->country_class;
    }

    /**
     * @return object
     */
    public function getConstant()
    {
        return $this->constant;
    }

    /**
     * @return object
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return object
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return object
     */
    public function getCountryRepository()
    {
        return $this->country_repository;
    }

    /**
     * @return object
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return object
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return object
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return mixed
     */
    public function getInstall()
    {
        return $this->install;
    }

    /**
     * @return object
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return object
     */
    public function getLoggerRepository()
    {
        return $this->logger_repository;
    }

    /**
     * @return object
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return object
     */
    public function getQueryAdapter()
    {
        return $this->query_adapter;
    }

    /**
     * @return object
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return object
     */
    public function getOnboardingAction()
    {
        return $this->onboarding_action;
    }

    /**
     * @return object
     */
    public function getMerchantTelemetryAction()
    {
        return $this->merchantTelemetry_action;
    }

    /**
     * @return object
     */
    public function getMerchantTelemetry()
    {
        return $this->merchantTelemetry;
    }

    /**
     * @return object
     */
    public function getLockRepository()
    {
        return $this->lock_repository;
    }

    /**
     * @return object
     */
    public function getModuleRepository()
    {
        return $this->module_repository;
    }

    /**
     * @return object
     */
    public function getOney()
    {
        return $this->oney;
    }

    /**
     * @return object
     */
    public function getOrderRepository()
    {
        return $this->order_repository;
    }

    /**
     * @return object
     */
    public function getOrderStateAction()
    {
        return $this->order_state_action;
    }

    /**
     * @return object
     */
    public function getOrderStateRepository()
    {
        return $this->order_state_repository;
    }

    /**
     * @return object
     */
    public function getPayplugOrderStateRepository()
    {
        return $this->payplug_order_state_repository;
    }

    /**
     * @return object
     */
    public function getOrderPaymentRepository()
    {
        return $this->order_payment_repository;
    }

    /**
     * @return object
     */
    public function getOrderSlip()
    {
        return $this->order_slip;
    }

    /**
     * @return object
     */
    public function getOrderState()
    {
        return $this->order_state;
    }

    /**
     * @return object
     */
    public function getOrderStateAdapter()
    {
        return $this->order_state_adapter;
    }

    /**
     * @return object
     */
    public function getPaymentAction()
    {
        return $this->payment_action;
    }

    /**
     * @return object
     */
    public function getPaymentMethodClass()
    {
        return $this->paymentMethod;
    }

    /**
     * @return object
     */
    public function getPaymentRepository()
    {
        return $this->payment_repository;
    }

    /**
     * @return object
     */
    public function getQueryRepository()
    {
        return $this->query_repository;
    }

    /**
     * @return object
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return object
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @return object
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @return object
     */
    public function getShopRepository()
    {
        return $this->shop_repository;
    }

    /**
     * @return object
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @return object
     */
    public function getTranslate()
    {
        return $this->translate;
    }

    /**
     * @return object
     */
    public function getTranslationAdapter()
    {
        return $this->translation_adapter;
    }

    /**
     * @return object
     */
    public function getTranslationClass()
    {
        return $this->translation;
    }

    /**
     * @return object
     */
    public function getValidate()
    {
        return $this->validate;
    }

    /**
     * @param object $address
     *
     * @return self
     */
    public function setAddress($address)
    {
        if (!is_object($address)) {
            throw new BadParameterException('Invalid argument, $address must be an AddressAdapter');
        }

        $this->address = $address;

        return $this;
    }

    /**
     * @param object $apiClass
     *
     * @return PluginEntity
     */
    public function setApiClass($apiClass)
    {
        $this->apiClass = $apiClass;

        return $this;
    }

    /**
     * @param object $api_rest
     *
     * @return self
     */
    public function setApiRestClass($api_rest)
    {
        if (!is_object($api_rest)) {
            throw new BadParameterException('Invalid argument, $api_rest must be ApiRest');
        }

        $this->api_rest = $api_rest;

        return $this;
    }

    /**
     * @param string $api_version
     *
     * @return self
     */
    public function setApiVersion($api_version)
    {
        if (!is_string($api_version) || !preg_match('/(\d{4})-(\d{2})-(\d{2})/', $api_version)) {
            throw new BadParameterException('Invalid argument, $api_url must be a a valid api url format');
        }

        $this->api_version = $api_version;

        return $this;
    }

    /**
     * @param object $assign
     *
     * @return self
     */
    public function setAssign($assign)
    {
        if (!is_object($assign)) {
            throw new BadParameterException('Invalid argument, $assign must be an AssignAdapter');
        }

        $this->assign = $assign;

        return $this;
    }

    /**
     * @param object $browser
     *
     * @return self
     */
    public function setBrowser($browser)
    {
        if (!is_object($browser)) {
            throw new BadParameterException('Invalid argument, $browser must be a Services/Browser');
        }

        $this->browser = $browser;

        return $this;
    }

    /**
     * @param object $cache
     *
     * @return self
     */
    public function setCache($cache)
    {
        if (!is_object($cache)) {
            throw new BadParameterException('Invalid argument, $cache must be a CacheRepository');
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * @param object $card_repository
     *
     * @return self
     */
    public function setCardRepository($card_repository)
    {
        if (!is_object($card_repository)) {
            throw new BadParameterException('Invalid argument, $card_repository must be an CardRepository');
        }

        $this->card_repository = $card_repository;

        return $this;
    }

    /**
     * @param object $cache_repository
     *
     * @return self
     */
    public function setCacheRepository($cache_repository)
    {
        if (!is_object($cache_repository)) {
            throw new BadParameterException('Invalid argument, $cache_repository must be an CacheRepository');
        }

        $this->cache_repository = $cache_repository;

        return $this;
    }

    /**
     * @param object $carrier
     *
     * @return self
     */
    public function setCarrier($carrier)
    {
        if (!is_object($carrier)) {
            throw new BadParameterException('Invalid argument, $carrier must be CarrierAdapter');
        }

        $this->carrier = $carrier;

        return $this;
    }

    /**
     * @param object $cart
     *
     * @return self
     */
    public function setCart($cart)
    {
        if (!is_object($cart)) {
            throw new BadParameterException('Invalid argument, $cart must be CartAdapter');
        }

        $this->cart = $cart;

        return $this;
    }

    /**
     * @param object $configuration
     *
     * @return self
     */
    public function setConfiguration($configuration)
    {
        if (!is_object($configuration)) {
            throw new BadParameterException('Invalid argument, $configuration must be a ConfigurationAdapter');
        }

        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @param object $cardAction
     *
     * @return self
     */
    public function setCardAction($cardAction)
    {
        if (!is_object($cardAction)) {
            throw new BadParameterException('Invalid argument, $cardAction must be a CardAction');
        }

        $this->card_action = $cardAction;

        return $this;
    }

    /**
     * @param object $configurationAction
     *
     * @return self
     */
    public function setConfigurationAction($configurationAction)
    {
        if (!is_object($configurationAction)) {
            throw new BadParameterException('Invalid argument, $configurationAction must be a ConfigurationAction');
        }

        $this->configuration_action = $configurationAction;

        return $this;
    }

    /**
     * @param $configuration_class
     *
     * @return $this
     */
    public function setConfigurationClass($configuration_class)
    {
        if (!is_object($configuration_class)) {
            throw new BadParameterException('Invalid argument, $configuration_class must be a setConfigurationClass');
        }

        $this->configuration_class = $configuration_class;

        return $this;
    }

    /**
     * @param object $country_class
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCountryClass($country_class)
    {
        if (!is_object($country_class)) {
            throw (new BadParameterException('Invalid argument, $country_class must be a setCountryClass'));
        }

        $this->country_class = $country_class;

        return $this;
    }

    /**
     * @param $constant
     *
     * @return $this
     */
    public function setConstant($constant)
    {
        if (!is_object($constant)) {
            throw new BadParameterException('Invalid argument, $constant must be a ConstantAdapter');
        }

        $this->constant = $constant;

        return $this;
    }

    /**
     * @param $context
     *
     * @return $this
     */
    public function setContext($context)
    {
        if (!is_object($context)) {
            throw new BadParameterException('Invalid argument, $context must be a ContextAdapter');
        }

        $this->context = $context;

        return $this;
    }

    /**
     * @param object $country
     *
     * @return self
     */
    public function setCountry($country)
    {
        if (!is_object($country)) {
            throw new BadParameterException('Invalid argument, $country must be a ContextAdapter');
        }

        $this->country = $country;

        return $this;
    }

    /**
     * @param object $country_repository
     *
     * @return self
     */
    public function setCountryRepository($country_repository)
    {
        if (!is_object($country_repository)) {
            throw new BadParameterException('Invalid argument, $country_repository must be an CountryRepository');
        }

        $this->country_repository = $country_repository;

        return $this;
    }

    /**
     * @param object $currency
     *
     * @return PluginEntity
     */
    public function setCurrency($currency)
    {
        if (!is_object($currency)) {
            throw new BadParameterException('Invalid Currency object, param $currency must be a CurrencyAdapter');
        }
        $this->currency = $currency;

        return $this;
    }

    /**
     * @param object $customer
     *
     * @return PluginEntity
     */
    public function setCustomer($customer)
    {
        if (!is_object($customer)) {
            throw new BadParameterException('Invalid Currency object, param $customer must be a CustomerAdapter');
        }
        $this->customer = $customer;

        return $this;
    }

    /**
     * @param mixed $dispatcher
     *
     * @return self
     */
    public function setDispatcher($dispatcher)
    {
        if (!is_object($dispatcher)) {
            throw new BadParameterException('Invalid argument, $dispatcher must be a DispatcherAdapter');
        }

        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * @param mixed $install
     *
     * @return PluginEntity
     */
    public function setInstall($install)
    {
        if (!is_object($install)) {
            throw new BadParameterException('Invalid argument, param $install must be a InstallRepository');
        }

        $this->install = $install;

        return $this;
    }

    /**
     * @param object $language
     *
     * @return PluginEntity
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param object $logger
     *
     * @return self
     */
    public function setLogger($logger)
    {
        if (!is_object($logger)) {
            throw new BadParameterException('Invalid argument, $logger must be a LoggerRepository');
        }

        $this->logger = $logger;

        return $this;
    }

    /**
     * @param object $logger_repository
     *
     * @return self
     */
    public function setLoggerRepository($logger_repository)
    {
        if (!is_object($logger_repository)) {
            throw new BadParameterException('Invalid argument, $logger_repository must be an LoggerRepository');
        }

        $this->logger_repository = $logger_repository;

        return $this;
    }

    /**
     * @param object $module
     * @param mixed $media
     *
     * @return self
     */
    public function setMedia($media)
    {
        if (!is_object($media)) {
            throw new BadParameterException('Invalid argument, $media must be a MediaAdapter');
        }

        $this->media = $media;

        return $this;
    }

    /**
     * @param mixed $merchantTelemetry
     *
     * @return self
     */
    public function setMerchantTelemetry($merchantTelemetry)
    {
        if (!is_object($merchantTelemetry)) {
            throw new BadParameterException('Invalid argument, $merchantTelemetry must be a MerchantTelemetry');
        }

        $this->merchantTelemetry = $merchantTelemetry;

        return $this;
    }

    /**
     * @param object $merchantTelemetryAction
     *
     * @return self
     */
    public function setMerchantTelemetryAction($merchantTelemetryAction)
    {
        if (!is_object($merchantTelemetryAction)) {
            throw new BadParameterException('Invalid argument, $merchantTelemetryAction must be a MerchantTelemetryAction');
        }

        $this->merchantTelemetry_action = $merchantTelemetryAction;

        return $this;
    }

    /**
     * @param mixed $message
     *
     * @return self
     */
    public function setMessage($message)
    {
        if (!is_object($message)) {
            throw new BadParameterException('Invalid argument, $message must be a MessageAdapter');
        }

        $this->message = $message;

        return $this;
    }

    /**
     * @param object $module
     *
     * @return self
     */
    public function setModule($module)
    {
        if (!is_object($module)) {
            throw new BadParameterException('Invalid argument, $module must be a ModuleAdapter');
        }

        $this->module = $module;

        return $this;
    }

    /**
     * @param object $module_repository
     *
     * @return self
     */
    public function setModuleRepository($module_repository)
    {
        if (!is_object($module_repository)) {
            throw new BadParameterException('Invalid argument, $module_repository must be an ModuleRepository');
        }

        $this->module_repository = $module_repository;

        return $this;
    }

    /**
     * @param object $lock_repository
     *
     * @return self
     */
    public function setLockRepository($lock_repository)
    {
        if (!is_object($lock_repository)) {
            throw new BadParameterException('Invalid argument, $lock_repository must be an LockRepository');
        }

        $this->lock_repository = $lock_repository;

        return $this;
    }

    /**
     * @param object $onboardingAction
     *
     * @return self
     */
    public function setOnboardingAction($onboardingAction)
    {
        if (!is_object($onboardingAction)) {
            throw new BadParameterException('Invalid argument, $onboardingAction must be a OnboardingAction');
        }

        $this->onboarding_action = $onboardingAction;

        return $this;
    }

    /**
     * @param object $oney
     *
     * @return self
     */
    public function setOney($oney)
    {
        if (!is_object($oney)) {
            throw new BadParameterException('Invalid argument, $oney must be a OneyRepository');
        }

        $this->oney = $oney;

        return $this;
    }

    /**
     * @param object $order_repository
     *
     * @return self
     */
    public function setOrderRepository($order_repository)
    {
        if (!is_object($order_repository)) {
            throw new BadParameterException('Invalid argument, $order_repository must be an OrderRepository');
        }

        $this->order_repository = $order_repository;

        return $this;
    }

    /**
     * @param object $orderStateAction
     *
     * @return self
     */
    public function setOrderStateAction($orderStateAction)
    {
        if (!is_object($orderStateAction)) {
            throw new BadParameterException('Invalid argument, $orderStateAction must be a OrderStateAction');
        }

        $this->order_state_action = $orderStateAction;

        return $this;
    }

    /**
     * @param object $order_state_repository
     *
     * @return self
     */
    public function setOrderStateRepository($order_state_repository)
    {
        if (!is_object($order_state_repository)) {
            throw new BadParameterException('Invalid argument, $order_state_repository must be an OrderStateRepository');
        }

        $this->order_state_repository = $order_state_repository;

        return $this;
    }

    /**
     * @param object $payplug_order_state_repository
     *
     * @return self
     */
    public function setPayplugOrderStateRepository($payplug_order_state_repository)
    {
        if (!is_object($payplug_order_state_repository)) {
            throw new BadParameterException('Invalid argument, $payplug_order_state_repository must be an PayplugOrderStateRepository');
        }

        $this->payplug_order_state_repository = $payplug_order_state_repository;

        return $this;
    }

    /**
     * @param mixed $order_payment_repository
     *
     * @return self
     */
    public function setOrderPaymentRepository($order_payment_repository)
    {
        if (!is_object($order_payment_repository)) {
            throw new BadParameterException('Invalid argument, $order_payment_repository must be an OrderPaymentRepository');
        }

        $this->order_payment_repository = $order_payment_repository;

        return $this;
    }

    /**
     * @param object $order_state
     *
     * @return self
     */
    public function setOrderState($order_state)
    {
        if (!is_object($order_state)) {
            throw new BadParameterException('Invalid argument, $order_state must be an OrderState');
        }

        $this->order_state = $order_state;

        return $this;
    }

    /**
     * @param object $order_slip
     *
     * @return self
     */
    public function setOrderSlip($order_slip)
    {
        if (!is_object($order_slip)) {
            throw new BadParameterException('Invalid argument, $order_slip must be an OrderSlip');
        }

        $this->order_slip = $order_slip;

        return $this;
    }

    /**
     * @param $order_state_adapter
     *
     * @return $this
     */
    public function setOrderStateAdapter($order_state_adapter)
    {
        if (!is_object($order_state_adapter)) {
            $error_msg = 'Invalid argument, $order_state_adapter must be an OrderStateAdapter';

            throw new BadParameterException($error_msg);
        }

        $this->order_state_adapter = $order_state_adapter;

        return $this;
    }

    /**
     * @param object $paymentAction
     *
     * @return self
     */
    public function setPaymentAction($paymentAction)
    {
        if (!is_object($paymentAction)) {
            throw new BadParameterException('Invalid argument, $paymentAction must be a PaymentAction');
        }

        $this->payment_action = $paymentAction;

        return $this;
    }

    /**
     * @param object $paymentMethod
     *
     * @return self
     */
    public function setPaymentMethodClass($paymentMethod)
    {
        if (!is_object($paymentMethod)) {
            throw new BadParameterException('Invalid argument, $paymentMethod must be a PaymentMethod');
        }

        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @param object $payment_repository
     *
     * @return self
     */
    public function setPaymentRepository($payment_repository)
    {
        if (!is_object($payment_repository)) {
            throw new BadParameterException('Invalid argument, $payment_repository must be an PaymentRepository');
        }

        $this->payment_repository = $payment_repository;

        return $this;
    }

    /**
     * @param object $product
     *
     * @return self
     */
    public function setProduct($product)
    {
        if (!is_object($product)) {
            throw new BadParameterException('Invalid argument, $product must be a ProductAdapter');
        }

        $this->product = $product;

        return $this;
    }

    /**
     * @param object $query_adapter
     *
     * @return self
     */
    public function setQueryAdapter($query_adapter)
    {
        if (!is_object($query_adapter)) {
            throw new BadParameterException('Invalid argument, $query_adapter must be a QueryAdapter');
        }

        $this->query_adapter = $query_adapter;

        return $this;
    }

    /**
     * @param object $query_repository
     *
     * @return self
     */
    public function setQueryRepository($query_repository)
    {
        if (!is_object($query_repository)) {
            throw new BadParameterException('Invalid argument, $query_repository must be a QueryRepository');
        }

        $this->query_repository = $query_repository;

        return $this;
    }

    /**
     * @param object $query
     * @param mixed $sql
     *
     * @return self
     */
    public function setSql($sql)
    {
        if (!is_object($sql)) {
            throw new BadParameterException('Invalid argument, $sql must be a SQLRepository');
        }

        $this->sql = $sql;

        return $this;
    }

    /**
     * @param object $routes
     *
     * @return self
     */
    public function setRoutes($routes)
    {
        if (!is_object($routes)) {
            throw new BadParameterException('Invalid argument, $routes must be a Services/Routes');
        }

        $this->routes = $routes;

        return $this;
    }

    /**
     * @param object $shop
     *
     * @return self
     */
    public function setShop($shop)
    {
        if (!is_object($shop)) {
            throw new BadParameterException('Invalid argument, $sql must be a ShopAdapter');
        }

        $this->shop = $shop;

        return $this;
    }

    /**
     * @param object $shop_repository
     *
     * @return self
     */
    public function setShopRepository($shop_repository)
    {
        if (!is_object($shop_repository)) {
            throw new BadParameterException('Invalid argument, $shop_repository must be an ShopRepository');
        }

        $this->shop_repository = $shop_repository;

        return $this;
    }

    /**
     * @param object $tools
     *
     * @return self
     */
    public function setTools($tools)
    {
        if (!is_object($tools)) {
            throw new BadParameterException('Invalid argument, $tools must be a ToolsAdapter');
        }

        $this->tools = $tools;

        return $this;
    }

    /**
     * @param object $translate
     *
     * @return self
     */
    public function setTranslate($translate)
    {
        if (!is_object($translate)) {
            throw new BadParameterException('Invalid argument, $translate must be a Translate');
        }

        $this->translate = $translate;

        return $this;
    }

    /**
     * @param object $translation_adatper
     * @param mixed $translation_adapter
     *
     * @return self
     */
    public function setTranslationAdapter($translation_adapter)
    {
        if (!is_object($translation_adapter)) {
            throw new BadParameterException('Invalid argument, $translation_adapter must be a TranslationAdapter');
        }

        $this->translation_adapter = $translation_adapter;

        return $this;
    }

    /**
     * @param object $translation
     *
     * @return self
     */
    public function setTranslationClass($translation)
    {
        if (!is_object($translation)) {
            throw new BadParameterException('Invalid argument, $translation must be a Translate');
        }

        $this->translation = $translation;

        return $this;
    }

    /**
     * @param object $validate
     *
     * @return self
     */
    public function setValidate($validate)
    {
        if (!is_object($validate)) {
            throw new BadParameterException('Invalid argument, $validate must be ValidateAdapter');
        }

        $this->validate = $validate;

        return $this;
    }
}
