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

function upgrade_module_3_1_4($object)
{
    $flag = true;

    if (Configuration::get('PAYPLUG_ONEY_TOS_URL')) {
        if (!Configuration::deleteByName('PAYPLUG_ONEY_TOS_URL')) {
            $flag = false;
        }
    }

    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $object->name . '_payment` (
    `id_' . $object->name . '_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_payment` VARCHAR(255) NULL,
    `payment_method` VARCHAR(255) NULL,
    `payment_url` VARCHAR(255) NULL,
    `payment_return_url` VARCHAR(255) NULL,
    `id_cart` INT(11) UNSIGNED NOT NULL,
    `cart_hash` VARCHAR(64) NULL,
    `authorized_at` INT(20) NOT NULL DEFAULT 0,
    `is_paid` TINYINT(1) NOT NULL DEFAULT 0,
    `is_pending` TINYINT(1) NOT NULL DEFAULT 0,
    `date_upd` DATETIME NULL, CONSTRAINT lock_cart_unique UNIQUE (id_cart)) ENGINE=' . _MYSQL_ENGINE_;

    return $flag && Db::getInstance()->execute($sql);
}
