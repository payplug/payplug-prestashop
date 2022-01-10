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

use PayPlug\src\entities\PluginEntity;
use PayPlug\src\repositories\HookRepository;
use PayPlug\src\repositories\InstallRepository;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\src\repositories\PluginRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayPlugDependencies
{
    /** @var AdminClass */
    private $adminClass;

    /** @var HookRepository */
    private $hook;

    /** @var InstallRepository */
    private $install;

    /** @var MyLogPHP */
    private $mylogphp;

    /** @var OneyRepository */
    public $oney;

    /** @var object */
    public $dependencies;

    /** @var object */
    public $payment;

    /** @var PluginEntity */
    private $plugin;

    public function __construct()
    {
        $this->initializeAccessors();
    }

    private function initializeAccessors()
    {
        $this->dependencies = new DependenciesClass();
        $this->setPlugin((new PluginRepository($this->dependencies))->getEntity());

        $this->api = new ApiClass($this->dependencies);
        $this->hook = $this->getPlugin()->getHook();
        $this->hookClass = new HookClass($this->dependencies);
        $this->install = $this->getPlugin()->getInstall();
        $this->oney = $this->getPlugin()->getOney();
        $this->payment = $this->getPlugin()->getPayment();
        $this->mylogphp = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
        return $this;
    }

    public function getDependency($dependency)
    {
        return $this->$dependency;
    }
}
