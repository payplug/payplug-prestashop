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

use PayPlug\src\entities\PluginEntity;
use PayPlug\src\repositories\HookRepository;
use PayPlug\src\repositories\InstallRepository;
use PayPlug\src\repositories\PluginRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayPlugDependencies
{
    /** @var HookRepository */
    public $hook;

    /** @var InstallRepository */
    private $install;

    /** @var object */
    public $payplug;

    /** @var PluginEntity */
    private $plugin;

    public function __construct()
    {
        $this->initializeAccessors();
    }

    private function initializeAccessors()
    {
        $this->payplug = new PayPlugClass();
        $this->setPlugin((new PluginRepository($this->payplug))->getEntity());

        $this->hook = $this->getPlugin()->getHook();
        $this->install = $this->getPlugin()->getInstall();
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
