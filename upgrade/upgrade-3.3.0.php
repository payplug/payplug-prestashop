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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PayPlug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_3_0($object)
{
    $flag = true;

    // Create new table to qualify order state
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $object->name . '_order_state` (
                `id_' . $object->name . '_order_state` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `id_order_state` INT(11) UNSIGNED NOT NULL,
                `type` VARCHAR(64) NOT NULL,
                `date_add` DATETIME NULL,
                `date_upd` DATETIME NULL,
                CONSTRAINT order_state_unique UNIQUE (id_order_state)
            ) ENGINE=' . _MYSQL_ENGINE_;

    $flag = $flag && Db::getInstance()->execute($sql);
    unset($sql);

    // Create default payplug order state
    $payplug_order_states = [
        'pending' => 'pending',
        'paid' => 'paid',
        'error' => 'error',
        'auth' => 'pending',
        'exp' => 'expired',
        'oney_pg' => 'pending',
        'cancelled' => 'cancelled',
        'refund' => 'refund',
    ];

    $date = date('Y-m-d');
    $payplug_order_states_sql = [];
    foreach ($payplug_order_states as $key => $type) {
        // update live status
        $id_order_state = Configuration::get('PAYPLUG_ORDER_STATE_' . Tools::strtoupper($key));
        $payplug_order_states_sql[] = '
            INSERT INTO `' . _DB_PREFIX_ . $object->name . '_order_state` 
            (`id_order_state`, `type`, `date_add`, `date_upd`) 
            VALUES (' . $id_order_state . ', "' . $type . '", "' . $date . '", "' . $date . '")';

        // update sandbox status
        $id_order_state = Configuration::get('PAYPLUG_ORDER_STATE_' . Tools::strtoupper($key) . '_TEST');
        $payplug_order_states_sql[] = '
            INSERT INTO `' . _DB_PREFIX_ . $object->name . '_order_state` 
            (`id_order_state`, `type`, `date_add`, `date_upd`) 
            VALUES (' . $id_order_state . ', "' . $type . '", "' . $date . '", "' . $date . '")';
    }

    if ($payplug_order_states_sql) {
        foreach ($payplug_order_states_sql as $sql) {
            $flag = $flag && Db::getInstance()->execute($sql);
            unset($sql);
        }
    }

    // plug module on the hook actionObjectOrderStateAddAfter
    $flag = $flag && $object->registerHook('actionObjectOrderStateAddAfter');

    // plug module on the hook actionObjectOrderStateUpdateAfter
    $flag = $flag && $object->registerHook('actionObjectOrderStateUpdateAfter');

    // plug module on the hook actionObjectOrderStateDeleteAfter
    $flag = $flag && $object->registerHook('actionObjectOrderStateDeleteAfter');

    // plug module on the hook displayAdminStatusesForm
    return $flag && $object->registerHook('displayAdminStatusesForm');
}
