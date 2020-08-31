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

function upgrade_module_2_19_0($object)
{
    $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
    $flag = true;

    //Configurations may not exist if upgrade is from an old enough version
    Configuration::deleteByName('PAYPLUG_ORDER_STATE_INST_CD');
    Configuration::deleteByName('PAYPLUG_ORDER_STATE_INST_CD_TEST');
    Configuration::deleteByName('PAYPLUG_ORDER_STATE_INST_PG');
    Configuration::deleteByName('PAYPLUG_ORDER_STATE_INST_PG_TEST');


    //sql
    $req_payplug_installment = '
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payplug_installment` (
            `id_payplug_installment` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_payment` VARCHAR(255) NULL,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_customer` INT(11) UNSIGNED NOT NULL,
            `order_total` INT(11) UNSIGNED NOT NULL,
            `step` VARCHAR(11) NOT NULL,
            `amount` INT(11) UNSIGNED NOT NULL,
            `status` INT(11) UNSIGNED NOT NULL,
            `scheduled_date` DATETIME NOT NULL
        ) ENGINE='._MYSQL_ENGINE_;
    try {
        $res_payplug_installment = DB::getInstance()->Execute($req_payplug_installment);
        if (!$res_payplug_installment) {
            $log->error('Fail to add table payplug_installment');
            $flag = false;
        }
    } catch (PrestaShopDatabaseException $e) {
        $log->error('Fail to add new table');
        $flag = false;
    }

    $object->uninstallTab();
    $object->uninstallModuleTab('AdminPayplug');
    if (!$object->installTab()) {
        $this->log_install->error('Fail to add installment tab.');
        $flag = false;
    }

    return $flag;
}
