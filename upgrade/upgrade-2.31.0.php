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

function upgrade_module_2_31_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '<')) {
        return true;
    }

    if (!defined('_PS_OS_PENDING_')) {
        define('_PS_OS_PENDING_', 0);
    }

    $flag = true;

    $states = [
        'auth_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_AUTH'),
        'auth_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_AUTH_TEST'),
        'exp_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_EXP'),
        'exp_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_EXP_TEST'),
        'inst_pg_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_INST_PG'),
        'inst_pg_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_INST_PG_TEST'),
        'pending_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_PENDING') != _PS_OS_PENDING_ ?
            (int) Configuration::get('PAYPLUG_ORDER_STATE_PENDING')
            : null,
        'pending_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST'),
        'error_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_ERROR') != _PS_OS_ERROR_ ?
            (int) Configuration::get('PAYPLUG_ORDER_STATE_ERROR')
            : null,
        'error_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST'),
        'oney_pending' => (int) Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG'),
        'oney_pending_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG_TEST'),
    ];

    foreach ($states as $state) {
        if ($state != null) {
            $s = new OrderState((int) $state);

            // update object only if order state exist
            if (Validate::isLoadedObject($s)) {
                $s->logable = false;
                $s->invoice = false;
                if (!$s->update()) {
                    $flag = false;
                }
            }
        }
    }

    // install table `payplug_order_payment`
    $req_payplug_order_payment = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $object->name . '_order_payment` (
            `id_' . $object->name . '_order_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_payment` VARCHAR(255) NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    $res_payplug_order_payment = Db::getInstance()->execute($req_payplug_order_payment);

    if (!$res_payplug_order_payment) {
        $flag = false;
    }

    try {
        // check if lock exists on id_cart
        $req_describe = 'DESCRIBE ' . _DB_PREFIX_ . $object->name . '_lock;';
        $res_describe = Db::getInstance()->executeS($req_describe);
        $lock_exists = false;
        if ($res_describe) {
            foreach ($res_describe as $field) {
                if ($field['Field'] == 'id_cart' && $field['Key'] == 'UNI') {
                    $lock_exists = true;
                }
            }
        }

        // check doesn't exist then add it
        if (!$lock_exists) {
            $req_truncate = 'TRUNCATE `' . _DB_PREFIX_ . $object->name . '_lock`;';
            $res_truncate = Db::getInstance()->execute($req_truncate);
            if (!$res_truncate) {
                $flag = false;
            }
            if ($flag) {
                $sql = 'ALTER TABLE `' . _DB_PREFIX_ . $object->name . '_lock` 
                        ADD CONSTRAINT lock_cart_unique UNIQUE (id_cart)';
                $res_alter = Db::getInstance()->execute($sql);
                if (!$res_alter) {
                    $flag = false;
                }
            }
            if ($flag) {
                $req_describe = 'DESCRIBE ' . _DB_PREFIX_ . $object->name . '_lock;';
                $res_describe = Db::getInstance()->executeS($req_describe);
                if ($res_describe) {
                    foreach ($res_describe as $field) {
                        if ($field['Field'] == 'id_cart' && $field['Key'] == 'UNI') {
                            $flag = $flag && true;
                        }
                    }
                } else {
                    $flag = false;
                }
            }
        }
    } catch (Exception $e) {
        $flag = false;
    }

    return $flag;
}
