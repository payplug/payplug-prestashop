<?php
/**
 * 2013 - 2016 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0($module)
{
    //sql
    $req_payplug_lock = '
      DROP TABLE IF EXISTS '._DB_PREFIX_.'payplug_lock';
    $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

    //configurations
    if (!Configuration::deleteByName('PAYPLUG_MODULE_KEY') ||
        !Configuration::deleteByName('PAYPLUG_MODULE_PUBLIC_KEY') ||
        !Configuration::deleteByName('PAYPLUG_MODULE_URL') ||
        !Configuration::deleteByName('PAYPLUG_MODULE_MIN_AMOUNT') ||
        !Configuration::deleteByName('PAYPLUG_MODULE_MAX_AMOUNT') ||
        !Configuration::deleteByName('PAYPLUG_MODULE_CURRENCIES') ||
        !Configuration::deleteByName('PAYPLUG_SANDBOX') ||
        !Configuration::deleteByName('PAYPLUG_DEBUG') ||
        !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID') ||
        !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID_TEST') ||
        !Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND') ||
        !Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND_TEST') ||
        !Configuration::deleteByName('PAYPLUG_ORDER_STATE_WAITING') ||
        !Configuration::deleteByName('PAYPLUG_ORDER_STATE_WAITING_TEST') ||
        !Configuration::deleteByName('PAYPLUG_ORDER_STATE_ERROR')
    ) {
        $conf = false;
    } else {
        $conf = true;
    }

    $install = $module->install(false);

    return ($res_payplug_lock && $conf && $install);
}
