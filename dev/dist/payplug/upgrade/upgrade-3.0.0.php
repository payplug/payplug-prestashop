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
 *  @author    Payplug SAS
 *  @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_0($object)
{
    $flag = true;

    // check order state paid & update if different than prestashop state
    $order_state = (int) Configuration::get('PS_OS_PAYMENT');
    $payplug_order_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_PAID');
    if ($order_state != $payplug_order_state) {
        $flag = $flag && Configuration::updateValue('PAYPLUG_ORDER_STATE_PAID', $order_state);
    }

    // plug module on the hook actionAdminControllerSetMedia
    $flag = $flag && $object->registerHook('actionAdminControllerSetMedia');

    // plug module on the hook displayAdminOrderMain
    return $flag && $object->registerHook('displayAdminOrderMain');
}
