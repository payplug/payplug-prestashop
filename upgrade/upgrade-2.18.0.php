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

function upgrade_module_2_18_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    $flag = true;

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        if (!Configuration::updateValue('PAYPLUG_VERSION_1_4', '2.18.0')) {
            $flag = false;
        }
    }

    //sql
    $req_payplug_installment = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $object->name . '_installment` (
            `id_' . $object->name . '_installment` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_payment` VARCHAR(255) NULL,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_customer` INT(11) UNSIGNED NOT NULL,
            `order_total` INT(11) UNSIGNED NOT NULL,
            `step` VARCHAR(4) NOT NULL,
            `amount` INT(11) UNSIGNED NOT NULL,
            `status` INT(11) UNSIGNED NOT NULL,
            `scheduled_date` DATETIME NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    try {
        $res_payplug_installment = DB::getInstance()->Execute($req_payplug_installment);
        if (!$res_payplug_installment) {
            $flag = false;
        }
    } catch (PrestaShopDatabaseException $e) {
        $flag = false;
    }

    if (!$object->installTab()) {
        $flag = false;
    }

    if (!$object->registerHook('displayBackOfficeHeader')) {
        $flag = false;
    }

    return $flag;
}
