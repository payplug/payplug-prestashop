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

namespace PayPlug\src\models\entities;

use PayPlug\src\exceptions\BadParameterException;

class PluginEntity
{
    /** @var object */
    private $address;

    /** @var object */
    private $apiClass;

    /** @var string */
    private $api_version;

    /** @var object */
    private $assign;

    /** @var object */
    private $cache;

    /** @var object */
    private $card;

    /** @var object */
    private $cart;

    /** @var object */
    private $carrier;

    /** @var object */
    private $configClass;

    /** @var object */
    private $configuration;

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
    private $hook;

    /** @var object */
    private $install;

//    /** @var object */
//    private $installment;

    /** @var object */
    private $logger;

    /** @var object */
    private $myLogPHP;

    /** @var object */
    private $media;

    /** @var object */
    private $module;

    /** @var object */
    private $oney;

    /** @var object */
    private $order_slip;

    /** @var object */
    private $order_state;

    /** @var object */
    private $order_state_adapter;

    /** @var object */
    private $payment;

    /** @var object */
    private $orderClass;

    /** @var object */
    private $product;

    /** @var object */
    private $query;

    /** @var object */
    private $sql;

    /** @var object */
    private $tools;

    /** @var object */
    private $translate;

    /** @var object */
    private $validate;

    /**
     * @return object
     */
    public function getApiClass()
    {
        return $this->apiClass;
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
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return object
     */
    public function getCard()
    {
        return $this->card;
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
     * @return object
     */
    public function getHook()
    {
        return $this->hook;
    }

    /**
     * @return mixed
     */
    public function getInstall()
    {
        return $this->install;
    }

//    /**
//     * @return object
//     */
//    public function getInstallment()
//    {
//        return $this->installment;
//    }
//
//    /**
//     * @param object $installment
//     * @return PluginEntity
//     */
//    public function setInstallment($installment)
//    {
//        $this->installment = $installment;
//        return $this;
//    }

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
    public function getProduct()
    {
        return $this->product;
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
    public function getOney()
    {
        return $this->oney;
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
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return object
     */
    public function getQuery()
    {
        return $this->query;
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
    public function getShop()
    {
        return $this->shop;
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
    public function getValidate()
    {
        return $this->validate;
    }

    /**
     * @param object $address
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setAddress($address)
    {
        if (!is_object($address)) {
            throw (new BadParameterException('Invalid argument, $address must be an AddressAdapter'));
        }

        $this->address = $address;

        return $this;
    }

    /**
     * @param string $api_version
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setApiVersion($api_version)
    {
        if (!is_string($api_version) || !preg_match('/(\d{4})-(\d{2})-(\d{2})/', $api_version)) {
            throw (new BadParameterException('Invalid argument, $api_url must be a a valid api url format'));
        }

        $this->api_version = $api_version;

        return $this;
    }

    /**
     * @param object $assign
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setAssign($assign)
    {
        if (!is_object($assign)) {
            throw (new BadParameterException('Invalid argument, $assign must be an AssignAdapter'));
        }

        $this->assign = $assign;

        return $this;
    }

    /**
     * @param object $cache
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCache($cache)
    {
        if (!is_object($cache)) {
            throw (new BadParameterException('Invalid argument, $card must be a CacheRepository'));
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * @param object $card
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCard($card)
    {
        if (!is_object($card)) {
            throw (new BadParameterException('Invalid argument, $card must be a CardRepository'));
        }

        $this->card = $card;

        return $this;
    }

    /**
     * @param object $carrier
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCarrier($carrier)
    {
        if (!is_object($carrier)) {
            throw (new BadParameterException('Invalid argument, $carrier must be CarrierAdapter'));
        }

        $this->carrier = $carrier;

        return $this;
    }

    /**
     * @param object $cart
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCart($cart)
    {
        if (!is_object($cart)) {
            throw (new BadParameterException('Invalid argument, $cart must be CartAdapter'));
        }

        $this->cart = $cart;

        return $this;
    }

    /**
     * @param object $configuration
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setConfiguration($configuration)
    {
        if (!is_object($configuration)) {
            throw (new BadParameterException('Invalid argument, $card must be a ConfigurationAdapter'));
        }

        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @param object $context
     * @param mixed  $constant
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setConstant($constant)
    {
        if (!is_object($constant)) {
            throw (new BadParameterException('Invalid argument, $constant must be a ConstantAdapter'));
        }

        $this->constant = $constant;

        return $this;
    }

    /**
     * @param object $context
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setContext($context)
    {
        if (!is_object($context)) {
            throw (new BadParameterException('Invalid argument, $card must be a ContextAdapter'));
        }

        $this->context = $context;

        return $this;
    }

    /**
     * @param object $country
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCountry($country)
    {
        if (!is_object($country)) {
            throw (new BadParameterException('Invalid argument, $card must be a ContextAdapter'));
        }

        $this->country = $country;

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
            throw (
            new BadParameterException(
                'Invalid Currency object, param $currency must be a CurrencyAdapter'
            )
            );
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
            throw (
            new BadParameterException(
                'Invalid Currency object, param $customer must be a CurrencyAdapter'
            )
            );
        }
        $this->customer = $customer;

        return $this;
    }

    /**
     * @param object $hook
     * @param mixed  $dispatcher
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setDispatcher($dispatcher)
    {
        if (!is_object($dispatcher)) {
            throw (new BadParameterException('Invalid argument, $dispatcher must be a DispatcherAdapter'));
        }

        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * @param object $hook
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setHook($hook)
    {
        if (!is_object($hook)) {
            throw (new BadParameterException('Invalid argument, $hook must be a HookRepository'));
        }

        $this->hook = $hook;

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
            throw (new BadParameterException('Invalid argument, param $install must be a InstallRepository'));
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
     * @throws BadParameterException
     *
     * @return self
     */
    public function setLogger($logger)
    {
        if (!is_object($logger)) {
            throw (new BadParameterException('Invalid argument, $card must be a LoggerRepository'));
        }

        $this->logger = $logger;

        return $this;
    }

    /**
     * @param object $module
     * @param mixed  $media
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setMedia($media)
    {
        if (!is_object($media)) {
            throw (new BadParameterException('Invalid argument, $media must be a MediaAdapter'));
        }

        $this->media = $media;

        return $this;
    }

    /**
     * @param object $module
     * @param mixed  $message
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setMessage($message)
    {
        if (!is_object($message)) {
            throw (new BadParameterException('Invalid argument, $message must be a MessageAdapter'));
        }

        $this->message = $message;

        return $this;
    }

    /**
     * @param object $module
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setModule($module)
    {
        if (!is_object($module)) {
            throw (new BadParameterException('Invalid argument, $module must be a ModuleAdapter'));
        }

        $this->module = $module;

        return $this;
    }

    /**
     * @param object $oney
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setOney($oney)
    {
        if (!is_object($oney)) {
            throw (new BadParameterException('Invalid argument, $card must be a OneyRepository'));
        }

        $this->oney = $oney;

        return $this;
    }

    /**
     * @param object $order_state
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setOrderState($order_state)
    {
        if (!is_object($order_state)) {
            throw (new BadParameterException('Invalid argument, $order_state must be an OrderState'));
        }

        $this->order_state = $order_state;

        return $this;
    }

    /**
     * @param object $order_slip
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setOrderSlip($order_slip)
    {
        if (!is_object($order_slip)) {
            throw (new BadParameterException('Invalid argument, $order_slip must be an OrderSlip'));
        }

        $this->order_slip = $order_slip;

        return $this;
    }

    /**
     * @param object $order_state
     * @param mixed  $order_state_adapter
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setOrderStateAdapter($order_state_adapter)
    {
        if (!is_object($order_state_adapter)) {
            $error_msg = 'Invalid argument, $order_state_adapter must be an OrderStateAdapter';

            throw (new BadParameterException($error_msg));
        }

        $this->order_state_adapter = $order_state_adapter;

        return $this;
    }

    /**
     * @param object $payment
     *
     * @return self
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @param object $product
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setProduct($product)
    {
        if (!is_object($product)) {
            throw (new BadParameterException('Invalid argument, $card must be a ProductAdapter'));
        }

        $this->product = $product;

        return $this;
    }

    /**
     * @param object $query
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setQuery($query)
    {
        if (!is_object($query)) {
            throw (new BadParameterException('Invalid argument, $card must be a QueryRepository'));
        }

        $this->query = $query;

        return $this;
    }

    /**
     * @param object $query
     * @param mixed  $sql
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setSql($sql)
    {
        if (!is_object($sql)) {
            throw (new BadParameterException('Invalid argument, $sql must be a SQLRepository'));
        }

        $this->sql = $sql;

        return $this;
    }

    /**
     * @param object $shop
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setShop($shop)
    {
        if (!is_object($shop)) {
            throw (new BadParameterException('Invalid argument, $sql must be a ShopAdapter'));
        }

        $this->shop = $shop;

        return $this;
    }

    /**
     * @param object $tools
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setTools($tools)
    {
        if (!is_object($tools)) {
            throw (new BadParameterException('Invalid argument, $card must be a ToolsAdapter'));
        }

        $this->tools = $tools;

        return $this;
    }

    /**
     * @param object $translate
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setTranslate($translate)
    {
        if (!is_object($translate)) {
            throw (new BadParameterException('Invalid argument, $translate must be a Translate'));
        }

        $this->translate = $translate;

        return $this;
    }

    /**
     * @param object $validate
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setValidate($validate)
    {
        if (!is_object($validate)) {
            throw (new BadParameterException('Invalid argument, $validate must be ValidateAdapter'));
        }

        $this->validate = $validate;

        return $this;
    }
}
