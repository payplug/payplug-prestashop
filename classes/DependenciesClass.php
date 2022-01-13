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
        $this->version = PAYPLUG_VERSION;
        $this->name = PAYPLUG_NAME;

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
        $this->cartClass = new CartClass();
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
}
