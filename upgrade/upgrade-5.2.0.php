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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @description Create the UPC operation, lock and alias tables
 *
 * @param mixed $object
 */
function upgrade_module_5_2_0($object)
{
    $logger = $object->payplug_dependencies->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 5.2.0');

    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_upc_operation` (
            `id_payplug_upc_operation` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `operation_id` VARCHAR(255) NOT NULL,
            `order_id` VARCHAR(255) NOT NULL,
            `exec_code` VARCHAR(20) NOT NULL,
            `outcome` VARCHAR(30) NOT NULL,
            `amount` INT(11) UNSIGNED NOT NULL,
            `treated` TINYINT(1) NOT NULL DEFAULT 0,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL,
            CONSTRAINT payplug_upc_operation_unique UNIQUE (operation_id),
            KEY payplug_upc_operation_order_id (order_id)) ENGINE=' . _MYSQL_ENGINE_;

    try {
        $flag_operation = Db::getInstance()->Execute($sql);
    } catch (PrestaShopDatabaseException $e) {
        $flag_operation = false;
    }

    $sql_lock = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_upc_lock` (
            `id_payplug_upc_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `lock_key` VARCHAR(255) NOT NULL,
            `expires_at` INT(11) UNSIGNED NOT NULL,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL,
            CONSTRAINT payplug_upc_lock_unique UNIQUE (lock_key)) ENGINE=' . _MYSQL_ENGINE_;

    try {
        $flag_lock = Db::getInstance()->Execute($sql_lock);
    } catch (PrestaShopDatabaseException $e) {
        $flag_lock = false;
    }

    $sql_alias = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_alias` (
            `id_payplug_alias` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_customer` INT(11) UNSIGNED NOT NULL,
            `alias_id` VARCHAR(255) NOT NULL,
            `currency` VARCHAR(3) NOT NULL,
            `identifier` VARCHAR(255) NOT NULL,
            `brand` VARCHAR(50) DEFAULT NULL,
            `last4` VARCHAR(4) DEFAULT NULL,
            `exp_month` VARCHAR(2) DEFAULT NULL,
            `exp_year` VARCHAR(4) DEFAULT NULL,
            `date_add` DATETIME NULL,
            CONSTRAINT payplug_alias_unique UNIQUE (alias_id),
            KEY payplug_alias_id_customer (id_customer)) ENGINE=' . _MYSQL_ENGINE_;

    try {
        $flag_alias = Db::getInstance()->Execute($sql_alias);
    } catch (PrestaShopDatabaseException $e) {
        $flag_alias = false;
    }

    $flag = $flag_operation && $flag_lock && $flag_alias;

    // Empty means the merchant never narrowed the Scalapay range: the account's own
    // authorized amounts from GET /account apply as-is.
    $flag = $flag && Configuration::updateValue('PAYPLUG_SCALAPAY_CUSTOM_MIN_AMOUNTS', '');
    $flag = $flag && Configuration::updateValue('PAYPLUG_SCALAPAY_CUSTOM_MAX_AMOUNTS', '');

    $logger->addLog('End upgrade script 5.2.0, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
