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

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayPlugDependencies
{
    /** @var ApiClass */
    public $apiClass;

    /** @var OneyRepository */
    public $oney;

    /** @var object */
    public $dependencies;

    /** @var object */
    public $payment;

    /** @var ConfigClass */
    private $configClass;

    /** @var HookRepository */
    private $hook;

    /** @var InstallRepository */
    private $install;

    /** @var MyLogPHP */
    private $mylogphp;

    /** @var PluginEntity */
    private $plugin;

    public function __construct()
    {
        $this->initializeAccessors();
    }

    public function getDependency($dependency)
    {
        return $this->{$dependency};
    }

    private function initializeAccessors()
    {
        $this->dependencies = new DependenciesClass();

        $this->apiClass = $this->dependencies->apiClass;
        $this->hook = $this->dependencies->getPlugin()->getHook();
        $this->install = $this->dependencies->getPlugin()->getInstall();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->payment = $this->dependencies->getPlugin()->getPayment();
        $this->hookClass = $this->dependencies->hookClass;
        $this->configClass = $this->dependencies->configClass;
        $this->mylogphp = new MyLogPHP(_PS_MODULE_DIR_ . $this->dependencies->name . '/log/install-log.csv');
    }
}
