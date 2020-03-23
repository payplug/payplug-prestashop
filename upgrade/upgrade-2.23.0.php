<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_.'payplug/classes/MyLogPHP.class.php');

function upgrade_module_2_23_0($object)
{
    $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
    $flag = true;

    //adding new configurations
    if (!Configuration::updateValue('PAYPLUG_DEFERRED', 0)
        || !Configuration::updateValue('PAYPLUG_DEFERRED_AUTO', 0)
        || !Configuration::updateValue('PAYPLUG_DEFERRED_STATE', 0)
        || !Configuration::updateValue('PAYPLUG_ORDER_STATE_AUTH', 0)
        || !Configuration::updateValue('PAYPLUG_ORDER_STATE_AUTH_TEST', 0)
        || !Configuration::updateValue('PAYPLUG_ORDER_STATE_EXP', 0)
        || !Configuration::updateValue('PAYPLUG_ORDER_STATE_EXP_TEST', 0)) {
        $log->error('Fail to add new configuration');
        $flag = false;
    }

    //hooking
    if (!$object->registerHook('actionOrderStatusUpdate')) {
        $log->error('Fail to hook');
        $flag = false;
    }

    //add order-state for deferred payment
    if (!$object->createOrderStates()) {
        $log->error('Fail to add order state');
        $flag = false;
    }

    return $flag;
}
