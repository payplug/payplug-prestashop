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

function upgrade_module_3_0_0($object)
{
    $flag = true;

    // install payplug payment cart
    $sql = '
        ALTER TABLE `'._DB_PREFIX_.'payplug_payment_cart`
        ADD COLUMN `date_upd` DATETIME NULL
        AFTER `is_pending`';
    $flag = $flag && Db::getInstance()->execute($sql);

    // install table `payplug_logger`
    $sql = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_logger` (
            `id_payplug_logger` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `process` VARCHAR(255) NOT NULL,
            `content` TEXT NOT NULL,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    $flag = $flag && Db::getInstance()->execute($sql);

    /*
     * Voir PayPlugCard : Auto Increment sur id_payplug_card (différent 1.6 1.7)
     * Voir pour migrations cartes existantes
     */

    /*
     * Ajouter la greffe vers le hook displayBeforeShoppingCartBlock pour les 1.6
     * et verifier qu'il n'y ait pas de conflit avec 1.7
     *
     * installOneyHook : rajouter displayBeforeShoppingCartBlock
     */

    return $flag;
}