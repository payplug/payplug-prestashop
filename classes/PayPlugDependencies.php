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

/**
 * Core file of PayPlug module
 */

require_once(_PS_MODULE_DIR_ . 'payplug/vendor/autoload.php');
require_once(_PS_MODULE_DIR_ . 'payplug/src/repositories/PluginRepository.php');


if (!defined('_PS_VERSION_')) {
    exit;
}


class PayPlugDependencies
{
    /** @var HookRepository */
    public $hook;

    /** @var PluginEntity */
    private $plugin;

    public function __construct()
    {
        $this->initializeAccessors();
    }

    private function initializeAccessors()
    {
        $this->setPlugin((new PayPlug\src\repositories\PluginRepository($this))->getEntity());

        $this->hook = $this->getPlugin()->getHook();
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
}
