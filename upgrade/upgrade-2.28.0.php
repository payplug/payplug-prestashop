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

function upgrade_module_2_28_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    if (!defined('_PS_OS_PENDING_')) {
        define('_PS_OS_PENDING_', 0);
    }

    $states = [
        'auth_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_AUTH'),
        'auth_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_AUTH_TEST'),
        'exp_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_EXP'),
        'exp_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_EXP_TEST'),
        'inst_pg_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_INST_PG'),
        'inst_pg_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_INST_PG_TEST'),
        'pending_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_PENDING') != _PS_OS_PENDING_ ?
            (int) Configuration::get('PAYPLUG_ORDER_STATE_PENDING') :
            null,
        'pending_state_test' => (int) Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST'),
        'error_state' => (int) Configuration::get('PAYPLUG_ORDER_STATE_ERROR') != _PS_OS_ERROR_ ?
            (int) Configuration::get('PAYPLUG_ORDER_STATE_ERROR') :
            null,
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
                $s->update();
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
        return false;
    }

    // install table `payplug_cache`
    $req_payplug_cache = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $object->name . '_cache` (
            `id_' . $object->name . '_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `cache_key` VARCHAR(255) NOT NULL,
            `cache_value` TEXT NOT NULL,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    $res_payplug_cache = Db::getInstance()->execute($req_payplug_cache);

    if (!$res_payplug_cache) {
        return false;
    }

    return $object->registerHook('actionAdminPerformanceControllerAfter');
}
