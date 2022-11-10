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

function upgrade_module_2_16_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    require_once _PS_MODULE_DIR_ . $object->name . '/classes/PayplugBackward.php';
    $flag = true;

    if (!Configuration::updateValue('PAYPLUG_INST', 0)
        || !Configuration::updateValue('PAYPLUG_INST_MODE', 3)
        || !Configuration::updateValue('PAYPLUG_INST_MIN_AMOUNT', 150)
    ) {
        $flag = false;
    }

    if (!$object->createOrderStates()) {
        $flag = false;
    }

    //sql
    $req_payplug_installment_cart = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $object->name . '_installment_cart` (
            `id_' . $object->name . '_installment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `is_pending` TINYINT(1) NOT NULL DEFAULT 0, 
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    try {
        $res_payplug_installment_cart = DB::getInstance()->Execute($req_payplug_installment_cart);
        if (!$res_payplug_installment_cart) {
            $flag = false;
        }
    } catch (PrestaShopDatabaseException $e) {
        $flag = false;
    }

    return $flag;
}
