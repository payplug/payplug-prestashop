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

namespace PayPlug\classes;

use PayPlug\src\application\adapter\TranslationAdapter;
use PayPlug\src\application\dependencies\PluginInit;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DependenciesClass
{
    public $adminClass;
    public $amountCurrencyClass;
    public $apiClass;
    public $cardClass;
    public $cartClass;
    public $configClass;
    public $configurationKeys = [
        'alloSaveCard' => [
            'name' => 'ALLOW_SAVE_CARD',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'applepay' => [
            'name' => 'APPLEPAY',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'amex' => [
            'name' => 'AMEX',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'bancontact' => [
            'name' => 'BANCONTACT',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'bancontactCountry' => [
            'name' => 'BANCONTACT_COUNTRY',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'companyId' => [
            'name' => 'COMPANY_ID',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'companyStatus' => [
            'name' => 'COMPANY_STATUS',
            'defaultValue' => '',
            'setConf' => 1,
        ],
        'companyIso' => [
            'name' => 'COMPANY_ISO',
            'defaultValue' => '',
            'setConf' => 1,
        ],
        'currencies' => [
            'name' => 'CURRENCIES',
            'defaultValue' => 'EUR',
            'setConf' => 1,
        ],
        'debugMode' => [
            'name' => 'DEBUG_MODE',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'deferred' => [
            'name' => 'DEFERRED',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'deferredState' => [
            'name' => 'DEFERRED_STATE',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'email' => [
            'name' => 'EMAIL',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'embeddedMode' => [
            'name' => 'EMBEDDED_MODE',
            'defaultValue' => 'redirected',
            'setConf' => 1,
        ],
        'inst' => [
            'name' => 'INST',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'instMinAmount' => [
            'name' => 'INST_MIN_AMOUNT',
            'defaultValue' => 150,
            'setConf' => 1,
        ],
        'instMode' => [
            'name' => 'INST_MODE',
            'defaultValue' => 3,
            'setConf' => 1,
        ],
        'keepCards' => [
            'name' => 'KEEP_CARDS',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'liveApiKey' => [
            'name' => 'LIVE_API_KEY',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'maxAmounts' => [
            'name' => 'MAX_AMOUNTS',
            'defaultValue' => 'EUR:1000000',
            'setConf' => 1,
        ],
        'minAmounts' => [
            'name' => 'MIN_AMOUNTS',
            'defaultValue' => 'EUR:1',
            'setConf' => 1,
        ],
        'offer' => [
            'name' => 'OFFER',
            'defaultValue' => '',
            'setConf' => 1,
        ],
        'oneClick' => [
            'name' => 'ONE_CLICK',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'oney' => [
            'name' => 'ONEY',
            'defaultValue' => null,
            'setConf' => 1,
        ],
        'oneyAllowedCountries' => [
            'name' => 'ONEY_ALLOWED_COUNTRIES',
            'defaultValue' => '',
            'setConf' => 1,
        ],
        'oneyMaxAmounts' => [
            'name' => 'ONEY_MAX_AMOUNTS',
            'defaultValue' => 'EUR:300000',
            'setConf' => 1,
        ],
        'oneyMinAmounts' => [
            'name' => 'ONEY_MIN_AMOUNTS',
            'defaultValue' => 'EUR:10000',
            'setConf' => 1,
        ],
        'oneyCustomMaxAmounts' => [
            'name' => 'ONEY_CUSTOM_MAX_AMOUNTS',
            'defaultValue' => 'EUR:3000',
            'setConf' => 1,
        ],
        'oneyCustomMinAmounts' => [
            'name' => 'ONEY_CUSTOM_MIN_AMOUNTS',
            'defaultValue' => 'EUR:100',
            'setConf' => 1,
        ],
        'oneyFees' => [
            'name' => 'ONEY_FEES',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'oneyOptimized' => [
            'name' => 'ONEY_OPTIMIZED',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'oneyProductCta' => [
            'name' => 'ONEY_PRODUCT_CTA',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'oneyCartCta' => [
            'name' => 'ONEY_CART_CTA',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'orderStateAuth' => [
            'name' => 'ORDER_STATE_AUTH',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateAuthTest' => [
            'name' => 'ORDER_STATE_AUTH_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateCancelled' => [
            'name' => 'ORDER_STATE_CANCELLED',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateCancelledTest' => [
            'name' => 'ORDER_STATE_CANCELLED_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateError' => [
            'name' => 'ORDER_STATE_ERROR',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateErrorTest' => [
            'name' => 'ORDER_STATE_ERROR_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateExp' => [
            'name' => 'ORDER_STATE_EXP',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateExpTest' => [
            'name' => 'ORDER_STATE_EXP_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateOneyPg' => [
            'name' => 'ORDER_STATE_ONEY_PG',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateOneyPgTest' => [
            'name' => 'ORDER_STATE_ONEY_PG_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStatePaid' => [
            'name' => 'ORDER_STATE_PAID',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStatePaidTest' => [
            'name' => 'ORDER_STATE_PAID_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStatePending' => [
            'name' => 'ORDER_STATE_PENDING',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStatePendingTest' => [
            'name' => 'ORDER_STATE_PENDING_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateRefund' => [
            'name' => 'ORDER_STATE_REFUND',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'orderStateRefundTest' => [
            'name' => 'ORDER_STATE_REFUND_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'publishableKey' => [
            'name' => 'PUBLISHABLE_KEY',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'publishableKeyTest' => [
            'name' => 'PUBLISHABLE_KEY_TEST',
            'defaultValue' => null,
            'setConf' => 0,
        ],
        'sandboxMode' => [
            'name' => 'SANDBOX_MODE',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'show' => [
            'name' => 'SHOW',
            'defaultValue' => 0,
            'setConf' => 1,
        ],
        'standard' => [
            'name' => 'STANDARD',
            'defaultValue' => 1,
            'setConf' => 1,
        ],
        'testApiKey' => [
            'name' => 'TEST_API_KEY',
            'defaultValue' => null,
            'setConf' => 1,
        ],
    ];
    public $installmentClass;
    public $hookClass;
    public $mediaClass;
    public $name;
    public $orderClass;
    public $paymentClass;
    public $payplugLock;
    public $version;
    public $refundClass;

    private $plugin;

    public function __construct()
    {
        $configuration = $this->getPluginConfiguration();
        $this->name = $configuration->moduleName;
        $this->version = $configuration->version;
        $this->initializeAccessors();
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function initializeAccessors()
    {
        $this->setPlugin((new PluginInit($this))->getEntity());

        $this->apiClass = new ApiClass($this);
        $this->applePayClass = new ApplePayClass($this);
        $this->amountCurrencyClass = new AmountCurrencyClass($this);
        $this->adminClass = new AdminClass($this);
        $this->cardClass = new CardClass($this);
        $this->payplugLock = new PayplugLock($this);
        $this->cartClass = new CartClass($this);
        $this->configClass = new ConfigClass($this);
        $this->installmentClass = new InstallmentClass($this);
        $this->hookClass = new HookClass($this);
        $this->mediaClass = new MediaClass($this);
        $this->orderClass = new OrderClass($this);
        $this->paymentClass = new PaymentClass($this);
        $this->refundClass = new RefundClass($this);
    }

    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * @description Return translation for a given string and context (optional)
     *
     * @param false $string
     * @param false $name
     *
     * @return string
     */
    public function l($string = false, $name = false)
    {
        if (!$string || !$this->getPlugin()->getValidate()->validate('isString', $string)) {
            return false;
        }

        return TranslationAdapter::translate($this->name, $string, $name);
    }

    /**
     * @return false|mixed
     */
    public function loadAdapterPresta()
    {
        //$AdapterClass = '\PayPlug\src\application\adapter\PrestashopAdapter' . _PS_VERSION_[0] . _PS_VERSION_[2];
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $AdapterClass = '\PayPlug\src\application\adapter\PrestashopAdapter16';
        } else {
            $AdapterClass = '\PayPlug\src\application\adapter\PrestashopAdapter17';
        }
        if (class_exists($AdapterClass)) {
            return new $AdapterClass();
        }

        return false;
    }

    /**
     * Get configuration key name for adapter module
     *
     * @param string $key
     *
     * @return string
     */
    public function getConfigurationKey($key = false)
    {
        if (!isset($this->configurationKeys[$key]) || !$key || !is_string($key)) {
            return false;
        }

        return $this->concatenateModuleNameTo($this->configurationKeys[$key]['name']);
    }

    /**
     * Get configuration key option for adapter module
     *
     * @param string $key
     * @param string $option
     *
     * @return bool|string
     */
    public function getConfigurationKeyOption($key, $option)
    {
        return $this->configurationKeys[$key][$option];
    }

    /**
     * Concatenate adapter module name to configuration key
     *
     * @param string $string
     *
     * @return string
     */
    public function concatenateModuleNameTo($string)
    {
        return Tools::strtoupper($this->name) . '_' . $string;
    }

    public function getPluginConfiguration()
    {
        $json_path = dirname(__FILE__) . '/../composer.json';
        if (!file_exists($json_path)) {
            return [];
        }

        $jsonContent = Tools::file_get_contents($json_path);
        if (!$jsonContent) {
            return [];
        }

        return json_decode($jsonContent);
    }
}
