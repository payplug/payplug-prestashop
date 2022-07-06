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
    private $admin;

    /** @var object */
    private $amountCurrencyClass;

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
    private $module;

    /** @var object */
    private $oney;

    /** @var object */
    private $order_state;

    /** @var object */
    private $order_state_specific;

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
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param object $myLogPHP
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
    public function getAmountCurrencyClass()
    {
        return $this->amountCurrencyClass;
    }

    /**
     * @param object $amountCurrencyClass
     * @return PluginEntity
     */
    public function setAmountCurrencyClass($amountCurrencyClass)
    {
        $this->amountCurrencyClass = $amountCurrencyClass;
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
    public function getAdmin()
    {
        return $this->admin;
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
    public function getOrderState()
    {
        return $this->order_state;
    }

    /**
     * @return object
     */
    public function getOrderStateSpecific()
    {
        return $this->order_state_specific;
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
     * @param object $admin
     * @return PluginEntity
     * @throws BadParameterException
     */
    public function setAdmin($admin)
    {
        if (!is_object($admin)) {
            throw (new BadParameterException('Invalid argument, $admin must be an AdminClass object'));
        }
        $this->admin = $admin;
        return $this;
    }

    /**
     * @param object $address
     * @return self
     * @throws BadParameterException
     */
    public function setAddress($address)
    {
        if (!is_object($address)) {
            throw (new BadParameterException('Invalid argument, $address must be an AddressSpecific'));
        }

        $this->address = $address;
        return $this;
    }

    /**
     * @param string $api_version
     * @return self
     * @throws BadParameterException
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
     * @return self
     * @throws BadParameterException
     */
    public function setAssign($assign)
    {
        if (!is_object($assign)) {
            throw (new BadParameterException('Invalid argument, $assign must be an AssignSpecific'));
        }

        $this->assign = $assign;
        return $this;
    }

    /**
     * @param object $cache
     * @return self
     * @throws BadParameterException
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
     * @return self
     * @throws BadParameterException
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
     * @return self
     * @throws BadParameterException
     */
    public function setCarrier($carrier)
    {
        if (!is_object($carrier)) {
            throw (new BadParameterException('Invalid argument, $carrier must be CarrierSpecific'));
        }

        $this->carrier = $carrier;
        return $this;
    }

    /**
     * @param object $cart
     * @return self
     * @throws BadParameterException
     */
    public function setCart($cart)
    {
        if (!is_object($cart)) {
            throw (new BadParameterException('Invalid argument, $cart must be CartSpecific'));
        }

        $this->cart = $cart;
        return $this;
    }

    /**
     * @param object $configuration
     * @return self
     * @throws BadParameterException
     */
    public function setConfiguration($configuration)
    {
        if (!is_object($configuration)) {
            throw (new BadParameterException('Invalid argument, $card must be a ConfigurationSpecific'));
        }

        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @param object $context
     * @return self
     * @throws BadParameterException
     */
    public function setConstant($constant)
    {
        if (!is_object($constant)) {
            throw (new BadParameterException('Invalid argument, $constant must be a ConstantSpecific'));
        }

        $this->constant = $constant;
        return $this;
    }

    /**
     * @param object $context
     * @return self
     * @throws BadParameterException
     */
    public function setContext($context)
    {
        if (!is_object($context)) {
            throw (new BadParameterException('Invalid argument, $card must be a ContextSpecific'));
        }

        $this->context = $context;
        return $this;
    }

    /**
     * @param object $country
     * @return self
     * @throws BadParameterException
     */
    public function setCountry($country)
    {
        if (!is_object($country)) {
            throw (new BadParameterException('Invalid argument, $card must be a ContextSpecific'));
        }

        $this->country = $country;
        return $this;
    }

    /**
     * @param object $currency
     * @return PluginEntity
     */
    public function setCurrency($currency)
    {
        if (!is_object($currency)) {
            throw (
            new BadParameterException(
                'Invalid Currency object, param $currency must be a CurrencySpecific'
            )
            );
        } else {
            $this->currency = $currency;
            return $this;
        }
    }

    /**
     * @param object $customer
     * @return PluginEntity
     */
    public function setCustomer($customer)
    {
        if (!is_object($customer)) {
            throw (
            new BadParameterException(
                'Invalid Currency object, param $customer must be a CurrencySpecific'
            )
            );
        } else {
            $this->customer = $customer;
            return $this;
        }
    }

    /**
     * @param object $hook
     * @return self
     * @throws BadParameterException
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
     * @return PluginEntity
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @param object $logger
     * @return self
     * @throws BadParameterException
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
     * @return self
     * @throws BadParameterException
     */
    public function setModule($module)
    {
        if (!is_object($module)) {
            throw (new BadParameterException('Invalid argument, $module must be a ModuleSpecific'));
        }

        $this->module = $module;
        return $this;
    }

    /**
     * @param object $oney
     * @return self
     * @throws BadParameterException
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
     * @return self
     * @throws BadParameterException
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
     * @param object $order_state
     * @return self
     * @throws BadParameterException
     */
    public function setOrderStateSpecific($order_state_specific)
    {
        if (!is_object($order_state_specific)) {
            $error_msg = 'Invalid argument, $order_state_specific must be an OrderStateSpecific';
            throw (new BadParameterException($error_msg));
        }

        $this->order_state_specific = $order_state_specific;
        return $this;
    }

    /**
     * @param object $payment
     * @return self
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * @param object $product
     * @return self
     * @throws BadParameterException
     */
    public function setProduct($product)
    {
        if (!is_object($product)) {
            throw (new BadParameterException('Invalid argument, $card must be a ProductSpecific'));
        }

        $this->product = $product;
        return $this;
    }

    /**
     * @param object $query
     * @return self
     * @throws BadParameterException
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
     * @return self
     * @throws BadParameterException
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
     * @param object $tools
     * @return self
     * @throws BadParameterException
     */
    public function setTools($tools)
    {
        if (!is_object($tools)) {
            throw (new BadParameterException('Invalid argument, $card must be a ToolsSpecific'));
        }

        $this->tools = $tools;
        return $this;
    }

    /**
     * @param object $translate
     * @return self
     * @throws BadParameterException
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
     * @return self
     * @throws BadParameterException
     */
    public function setValidate($validate)
    {
        if (!is_object($validate)) {
            throw (new BadParameterException('Invalid argument, $validate must be ValidateSpecific'));
        }

        $this->validate = $validate;
        return $this;
    }
}
