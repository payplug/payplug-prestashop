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

namespace PayPlug\classes;

use PayPlug\src\application\adapter\TranslationAdapter;
use PayPlug\src\application\dependencies\PluginInit;
use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\src\utilities\helpers\ConfigurationHelper;
use PayPlug\src\utilities\helpers\CookiesHelper;
use PayPlug\src\utilities\helpers\FilesHelper;
use PayPlug\src\utilities\helpers\PhoneHelper;
use PayPlug\src\utilities\helpers\UserHelper;
use PayPlug\src\utilities\validators\accountValidator;
use PayPlug\src\utilities\validators\browserValidator;
use PayPlug\src\utilities\validators\cardValidator;
use PayPlug\src\utilities\validators\lockValidator;
use PayPlug\src\utilities\validators\loggerValidator;
use PayPlug\src\utilities\validators\moduleValidator;
use PayPlug\src\utilities\validators\orderValidator;
use PayPlug\src\utilities\validators\paymentValidator;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DependenciesClass
{
    public $adminClass;
    public $amountCurrencyClass;
    public $applePayClass;
    public $cartClass;
    public $configClass;
    public $hookClass;
    public $mediaClass;
    public $name;
    public $orderClass;
    public $paymentClass;
    public $payplugLock;
    public $version;
    public $refundClass;

    private $classes;
    private $plugin;
    private $helpers;
    private $repositories;
    private $validators;

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
        $this->setvalidators();
        $this->setHelpers();
        $this->setPlugin((new PluginInit($this))->getEntity());

        $this->applePayClass = new ApplePayClass($this);
        $this->amountCurrencyClass = new AmountCurrencyClass($this);
        $this->adminClass = new AdminClass($this);
        $this->payplugLock = new PayplugLock($this);
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
        $AdapterClass = '\PayPlug\src\application\adapter\PrestashopAdapter17';
        if (class_exists($AdapterClass)) {
            return new $AdapterClass();
        }

        return false;
    }

    public function getPluginConfiguration()
    {
        $json_path = dirname(__FILE__) . '/../composer.json';
        if (!file_exists($json_path)) {
            return [];
        }

        $jsonContent = \Tools::file_get_contents($json_path);
        if (!$jsonContent) {
            return [];
        }

        return json_decode($jsonContent);
    }

    public function getValidators()
    {
        return $this->validators;
    }

    public function getHelpers()
    {
        return $this->helpers;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    private function setvalidators()
    {
        $this->validators = [
            'account' => new accountValidator(),
            'browser' => new browserValidator(),
            'card' => new cardValidator(),
            'lock' => new lockValidator(),
            'logger' => new loggerValidator(),
            'module' => new moduleValidator(),
            'order' => new orderValidator(),
            'payment' => new paymentValidator(),
        ];
    }

    private function setHelpers()
    {
        $this->helpers = [
            'amount' => new AmountHelper($this),
            'configuration' => new ConfigurationHelper(),
            'cookies' => new CookiesHelper($this),
            'files' => new FilesHelper(),
            'phone' => new PhoneHelper(),
            'user' => new UserHelper(),
        ];
    }
}
