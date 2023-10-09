<?php
/**
 * 2013 - 2023 Payplug SAS.
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
 * @copyright 2013 - 2023 Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_4_0($object)
{
    $flag = true;

    $logger = $object->module->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 4.4.0');

    $payment_table = _DB_PREFIX_ . $object->name . '_payment';

    $alter_table_sqls = [
        'ALTER TABLE `' . $payment_table . '` ADD COLUMN `resource_id` VARCHAR(255) NULL AFTER `id_payment`',
        'UPDATE `' . $payment_table . '` SET `resource_id` = `id_payment`',
        'ALTER TABLE `' . $payment_table . '` DROP COLUMN `id_payment`',
        'ALTER TABLE `' . $payment_table . '` ADD COLUMN `method` VARCHAR(255) NULL AFTER `payment_method`',
        'UPDATE `' . $payment_table . '` SET `method` = `payment_method`',
        'ALTER TABLE `' . $payment_table . '` DROP COLUMN `payment_method`',
        'ALTER TABLE `' . $payment_table . '` ADD COLUMN `schedules` TEXT NULL AFTER `cart_hash`',
        'ALTER TABLE `' . $payment_table . '` DROP COLUMN `payment_url`',
        'ALTER TABLE `' . $payment_table . '` DROP COLUMN `payment_return_url`',
        'ALTER TABLE `' . $payment_table . '` DROP COLUMN `authorized_at`',
        'ALTER TABLE `' . $payment_table . '` DROP COLUMN `is_paid`',
        'ALTER TABLE `' . $payment_table . '` DROP COLUMN `is_pending`',
    ];

    foreach ($alter_table_sqls as $alter_table_sql) {
        if ($flag) {
            $exec = Db::getInstance()->execute($alter_table_sql);
            if (!$exec) {
                $logger->addLog('An error occured while executing sql: ' . $alter_table_sql);
            }
            $flag = $flag && $exec;
        }
    }

    // Hydrate payplug_payment table with datas from payplug_installment table
    $installments = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . $object->name . '_installment`');
    $schedules = [];
    if ($installments) {
        foreach ($installments as $installment) {
            $schedules[$installment['id_installment']][] = [
                'resource_id' => $installment['id_payment'],
                'step' => $installment['step'],
                'amount' => $installment['amount'],
                'status' => $installment['status'],
                'scheduled_date' => $installment['scheduled_date'],
            ];
        }
    }

    if (!empty($schedules)) {
        foreach ($schedules as $resource_id => $schedule) {
            if ($flag) {
                $hydrate_sql = 'UPDATE
                                `' . $payment_table . '`
                                SET `schedules` = ' . json_encode($schedule) . '
                                WHERE `resource_id` = ' . $resource_id;
                $exec = Db::getInstance()->execute($hydrate_sql);
                if (!$exec) {
                    $logger->addLog('An error occured while executing sql: ' . $hydrate_sql);
                }
                $flag = $flag && $exec;
            }
        }
    }

    $tables_to_drop = [
        'installment',
        'installment_cart',
        'payment_cart',
    ];
    foreach ($tables_to_drop as $table_to_drop) {
        if ($flag) {
            $sql = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . $object->name . '_' . $table_to_drop;
            $exec = Db::getInstance()->execute($sql);
            if (!$exec) {
                $logger->addLog('An error occured while executing sql: ' . $sql);
            }
            $flag = $flag && $exec;
        }
    }

    $logger->addLog('End upgrade script 4.4.0, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
