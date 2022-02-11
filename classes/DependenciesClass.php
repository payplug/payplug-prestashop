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

use PayPlug\src\repositories\PluginRepository;
use PayPlug\src\specific\TranslationSpecific;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'payplug/constants.php');

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
            'defaultValue' => 0
        ],
        'bancontact' => [
            'name' => 'BANCONTACT',
            'defaultValue' => null
        ],
        'companyId' => [
            'name' => 'COMPANY_ID',
            'defaultValue' => null
        ],
        'companyStatus' => [
            'name' => 'COMPANY_STATUS',
            'defaultValue' => ''
        ],
        'companyIso' => [
            'name' => 'COMPANY_ISO',
            'defaultValue' => ''
        ],
        'currencies' => [
            'name' => 'CURRENCIES',
            'defaultValue' => 'EUR'
        ],
        'debugMode' => [
            'name' => 'DEBUG_MODE',
            'defaultValue' => 0
        ],
        'deferred' => [
            'name' => 'DEFERRED',
            'defaultValue' => 0
        ],
        'deferredAuto' => [
            'name' => 'DEFERRED_AUTO',
            'defaultValue' => 0
        ],
        'deferredState' => [
            'name' => 'DEFERRED_STATE',
            'defaultValue' => 0
        ],
        'email' => [
            'name' => 'EMAIL',
            'defaultValue' => null
        ],
        'embeddedMode' => [
            'name' => 'EMBEDDED_MODE',
            'defaultValue' => 'redirected'
        ],
        'inst' => [
            'name' => 'INST',
            'defaultValue' => null
        ],
        'instMinAmount' => [
            'name' => 'INST_MIN_AMOUNT',
            'defaultValue' => 150
        ],
        'instMode' => [
            'name' => 'INST_MODE',
            'defaultValue' => 3
        ],
        'keepCards' => [
            'name' => 'KEEP_CARDS',
            'defaultValue' => 0
        ],
        'liveApiKey' => [
            'name' => 'LIVE_API_KEY',
            'defaultValue' => null
        ],
        'maxAmounts' => [
            'name' => 'MAX_AMOUNTS',
            'defaultValue' => 'EUR:1000000'
        ],
        'minAmounts' => [
            'name' => 'MIN_AMOUNTS',
            'defaultValue' => 'EUR:1'
        ],
        'offer' => [
            'name' => 'OFFER',
            'defaultValue' => ''
        ],
        'oneClick' => [
            'name' => 'ONE_CLICK',
            'defaultValue' => null
        ],
        'oney' => [
            'name' => 'ONEY',
            'defaultValue' => null
        ],
        'oneyAllowedCountries' => [
            'name' => 'ONEY_ALLOWED_COUNTRIES',
            'defaultValue' => ''
        ],
        'oneyMaxAmounts' => [
            'name' => 'ONEY_MAX_AMOUNTS',
            'defaultValue' => 'EUR:300000'
        ],
        'oneyMinAmounts' => [
            'name' => 'ONEY_MIN_AMOUNTS',
            'defaultValue' => 'EUR:10000'
        ],
        'oneyCustomMaxAmounts' => [
            'name' => 'ONEY_CUSTOM_MAX_AMOUNTS',
            'defaultValue' => 'EUR:3000'
        ],
        'oneyCustomMinAmounts' => [
            'name' => 'ONEY_CUSTOM_MIN_AMOUNTS',
            'defaultValue' => 'EUR:100'
        ],
        'oneyFees' => [
            'name' => 'ONEY_FEES',
            'defaultValue' => 1
        ],
        'oneyOptimized' => [
            'name' => 'ONEY_OPTIMIZED',
            'defaultValue' => 0
        ],
        'publishableKey' => [
            'name' => 'PUBLISHABLE_KEY',
            'defaultValue' => null
        ],
        'publishableKeyTest' => [
            'name' => 'PUBLISHABLE_KEY_TEST',
            'defaultValue' => null
        ],
        'sandboxMode' => [
            'name' => 'SANDBOX_MODE',
            'defaultValue' => 1
        ],
        'show' => [
            'name' => 'SHOW',
            'defaultValue' => 0
        ],
        'standard' => [
            'name' => 'STANDARD',
            'defaultValue' => 1
        ],
        'testApiKey' => [
            'name' => 'TEST_API_KEY',
            'defaultValue' => null
        ],
    ];
    public $hookClass;
    public $mediaClass;
    public $name;
    public $orderClass;
    public $paymentClass;
    public $version;
    public $refundClass;

    private $plugin;

    public function __construct()
    {
        $this->version = MODULE_VERSION;
        $this->name = MODULE_NAME;

        $this->initializeAccessors();
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function initializeAccessors()
    {
        $this->setPlugin((new PluginRepository($this))->getEntity());

        $this->amountCurrencyClass = $this->getPlugin()->getAmountCurrencyClass();
        $this->adminClass = new AdminClass($this);
        $this->apiClass = new ApiClass($this);
        $this->cardClass = new CardClass($this);
        $this->cartClass = new CartClass($this);
        $this->configClass = new ConfigClass($this);
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
     * @return string
     */
    public function l($string = false, $name = false)
    {
        if (!$string || !$this->getPlugin()->getValidate()->validate('isString', $string)) {
            return false;
        }
        return TranslationSpecific::translate($this->name, $string, $name);
    }

    /**
     * @return false|mixed
     */
    public function loadSpecificPresta()
    {
        $PrestashopSpecificClass = '\PayPlug\src\specific\PrestashopSpecific' . _PS_VERSION_[0] . _PS_VERSION_[2];
        if (class_exists($PrestashopSpecificClass)) {
            return new $PrestashopSpecificClass();
        }

        return false;
    }

    /**
     * Get configuration key name for specific module
     * @param string $key
     * @return string
     */
    public function getConfigurationKey($key)
    {
        return $this->concatenateModuleNameTo($this->configurationKeys[$key]['name']);
    }

    /**
     * Get configuration default value for specific module
     * @param string $key
     * @return string
     */
    public function getConfigurationDefaultValue($key)
    {
        return $this->configurationKeys[$key]['defaultValue'];
    }

    /**
     * Concatenate specific module name to configuration key
     * @param string $string
     * @return string
     */
    public function concatenateModuleNameTo($string)
    {
        return Tools::strtoupper($this->name) . "_" . $string;
    }
}
