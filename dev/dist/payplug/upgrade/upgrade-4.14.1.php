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

function upgrade_module_4_14_1($object)
{
    $flag = true;
    $exec = true;

    $logger = $object->module->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 4.14.1');

    $sql_column_exists = 'SELECT * 
	FROM information_schema.COLUMNS 
	WHERE TABLE_NAME = "' . _DB_PREFIX_ . $object->name . '_payment" 
	AND COLUMN_NAME = "is_live"';
    $column_exists = Db::getInstance()->execute($sql_column_exists);

    if (!$column_exists) {
        $alter_table_sql = 'ALTER TABLE `' . _DB_PREFIX_ . $object->name . '_payment`
                        ADD COLUMN `is_live` TINYINT(1) NOT NULL DEFAULT 1
                        AFTER `resource_id`';

        try {
            $exec = Db::getInstance()->execute($alter_table_sql);
        } catch (Exception $e) {
            $logger->addLog('An error occured while executing sql: ' . $alter_table_sql, 'error');
            $logger->addLog($e, 'error');
            $flag = false;
        }
    }
    $flag = $flag && $exec;

    $flag = $flag && Configuration::updateValue('PAYPLUG_CLIENT_DATA', '{}');

    $payment_methods = json_decode(Configuration::get('PAYPLUG_PAYMENT_METHODS'), true);
    if (isset($payment_methods['sofort'])) {
        unset($payment_methods['sofort']);
    }
    $flag = $flag && Configuration::updateValue('PAYPLUG_PAYMENT_METHODS', json_encode($payment_methods));

    $countries = json_decode(Configuration::get('PAYPLUG_COUNTRIES'), true);
    if (isset($countries['sofort'])) {
        unset($countries['sofort']);
    }
    $flag = $flag && Configuration::updateValue('PAYPLUG_COUNTRIES', json_encode($countries));

    $amounts = json_decode(Configuration::get('PAYPLUG_AMOUNTS'), true);
    if (isset($amounts['sofort'])) {
        unset($amounts['sofort']);
    }
    $flag = $flag && Configuration::updateValue('PAYPLUG_AMOUNTS', json_encode($amounts));

    $logger->addLog('End upgrade script 4.14.1, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
