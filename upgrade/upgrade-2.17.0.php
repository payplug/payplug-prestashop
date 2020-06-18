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

function upgrade_module_2_17_0($object)
{
    $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
    $flag = true;

    if (!Configuration::updateValue('PAYPLUG_INST', null)
        || !Configuration::updateValue('PAYPLUG_INST_MODE', 3)
        || !Configuration::updateValue('PAYPLUG_INST_MIN_AMOUNT', 150)
    ) {
        $log->error('Fail to add new configuration');
        $flag = false;
    }

    if (!$object->createOrderStates()) {
        $log->error('Fail to create new order states');
        $flag = false;
    }

    //sql
    $req_payplug_installment_cart = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payplug_installment_cart` (
            `id_payplug_installment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `is_pending` TINYINT(1) NOT NULL DEFAULT 0,
            `date_upd` DATETIME NULL
            ) ENGINE='._MYSQL_ENGINE_;
    try {
        $res_payplug_installment_cart = DB::getInstance()->Execute($req_payplug_installment_cart);
        if (!$res_payplug_installment_cart) {
            $log->error('Fail to add table installment_cart');
            $flag = false;
        }
    } catch (PrestaShopDatabaseException $e) {
        $log->error('Fail to add new table');
        $flag = false;
    }

    return $flag;
}
