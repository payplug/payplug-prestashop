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

require_once(_PS_MODULE_DIR_ . 'payplug/classes/MyLogPHP.class.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @description Install PayPlug Cache DB in case of an upgrade of the module.
 *
 * @return boolean
 */
function upgrade_module_2_29_0()
{
    $flag = true;

    // install table `payplug_cache`
    $sql = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_cache` (
            `id_payplug_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `cache_key` VARCHAR(255) NOT NULL,
            `cache_value` TEXT NOT NULL,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    $flag = $flag && Db::getInstance()->execute($sql);

    return $flag;
}
