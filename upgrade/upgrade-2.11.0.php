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

function upgrade_module_2_11_0()
{
    //sql
    $req_payplug_payment_cart = '
        ALTER TABLE `'._DB_PREFIX_.'payplug_payment_cart`
        ADD COLUMN `is_pending` TINYINT(1) NOT NULL DEFAULT 0
        AFTER `id_cart`';
    try {
        $res_payplug_payment_cart = DB::getInstance()->Execute($req_payplug_payment_cart);
    } catch (PrestaShopDatabaseException $e) {
        return true;
    }

    return $res_payplug_payment_cart;
}
