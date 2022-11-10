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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PayPlug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    //sql
    $req_payplug_lock = '
      DROP TABLE IF EXISTS ' . _DB_PREFIX_ . $object->name . '_lock';
    $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

    //configurations
    if (!Configuration::deleteByName('PAYPLUG_MODULE_KEY')
        || !Configuration::deleteByName('PAYPLUG_MODULE_PUBLIC_KEY')
        || !Configuration::deleteByName('PAYPLUG_MODULE_URL')
        || !Configuration::deleteByName('PAYPLUG_MODULE_MIN_AMOUNT')
        || !Configuration::deleteByName('PAYPLUG_MODULE_MAX_AMOUNT')
        || !Configuration::deleteByName('PAYPLUG_MODULE_CURRENCIES')
        || !Configuration::deleteByName('PAYPLUG_SANDBOX')
        || !Configuration::deleteByName('PAYPLUG_DEBUG')
        || !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID')
        || !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID_TEST')
        || !Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND')
        || !Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND_TEST')
        || !Configuration::deleteByName('PAYPLUG_ORDER_STATE_WAITING')
        || !Configuration::deleteByName('PAYPLUG_ORDER_STATE_WAITING_TEST')
        || !Configuration::deleteByName('PAYPLUG_ORDER_STATE_ERROR')
    ) {
        $conf = false;
    } else {
        $conf = true;
    }

    $install = $object->install(false);

    return $res_payplug_lock && $conf && $install;
}
