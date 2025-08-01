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

function upgrade_module_4_12_0($object)
{
    $logger = $object->payplug_dependencies->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 4.12.0');

    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_queue` (
            `id_payplug_queue` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `resource_id` VARCHAR(255) NULL,
            `type` VARCHAR(64) NOT NULL,
            `date_add` DATETIME NULL, 
            `date_upd` DATETIME NULL,
            `treated` TINYINT(1) NOT NULL DEFAULT 0, 
            CONSTRAINT payplug_queue_unique UNIQUE (id_payplug_queue)) ENGINE=' . _MYSQL_ENGINE_;

    try {
        $flag = Db::getInstance()->Execute($sql);
    } catch (PrestaShopDatabaseException $e) {
        $flag = false;
    }

    $logger->addLog('End upgrade script 4.12.0, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
