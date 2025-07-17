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

function upgrade_module_4_14_2($object)
{
    $flag = true;

    $logger = $object->payplug_dependencies->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 4.14.2');

    try {
        $sql = 'ALTER TABLE `' . _DB_PREFIX_ . $object->name . '_payment`
                ADD COLUMN `is_live` TINYINT(1) NOT NULL DEFAULT 1
                AFTER `resource_id`';
        if (!Db::getInstance()->execute($sql)) {
            $logger->addLog('The table can\'t be altered', 'error');
        }
    } catch (Exception $e) {
        $logger->addLog('An error occured while executing sql: ' . $sql, 'error');
        $logger->addLog($e->getMessage(), 'error');
    }

    $logger->addLog('End upgrade script 4.14.2, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
