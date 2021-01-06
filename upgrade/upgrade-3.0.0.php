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

use PayPlug\classes\MyLogPHP;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_0($object)
{
    $flag = true;

    // install table `payplug_logger`
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_logger` (
            `id_payplug_logger` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `process` VARCHAR(255) NOT NULL,
            `content` TEXT NOT NULL,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    $flag = $flag && Db::getInstance()->execute($sql);

    if (!defined('_PS_OS_PENDING_')) {
        define('_PS_OS_PENDING_', 0);
    }

    $states = [
        'auth_state' => (int)Configuration::get('PAYPLUG_ORDER_STATE_AUTH'),
        'auth_state_test' => (int)Configuration::get('PAYPLUG_ORDER_STATE_AUTH_TEST'),
        'exp_state' => (int)Configuration::get('PAYPLUG_ORDER_STATE_EXP'),
        'exp_state_test' => (int)Configuration::get('PAYPLUG_ORDER_STATE_EXP_TEST'),
        'inst_pg_state' => (int)Configuration::get('PAYPLUG_ORDER_STATE_INST_PG'),
        'inst_pg_state_test' => (int)Configuration::get('PAYPLUG_ORDER_STATE_INST_PG_TEST'),
        'pending_state' => (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING') != _PS_OS_PENDING_ ?
            (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING') :
            null,
        'pending_state_test' => (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST'),
        'error_state' => (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR') != _PS_OS_ERROR_ ?
            (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR') :
            null,
        'error_state_test' => (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST'),
        'oney_pending' => (int)Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG'),
        'oney_pending_test' => (int)Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG_TEST'),
    ];
    foreach ($states as $state) {
        if ($state != null) {
            $s = new OrderState((int)$state);

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
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_order_payment` (
            `id_payplug_order_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_payment` VARCHAR(255) NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_;

    $res_payplug_order_payment = Db::getInstance()->execute($req_payplug_order_payment);

    if (!$res_payplug_order_payment) {
        $flag = false;
    }

    $flag = $flag && Configuration::updateValue('PAYPLUG_COMPANY_ID_TEST', '');

    // check order state paid & update if different than prestashop state
    $order_state = (int)Configuration::get('PS_OS_PAYMENT');
    $payplug_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID');
    if ($order_state != $payplug_order_state) {
        $flag = $flag && Configuration::updateValue('PAYPLUG_ORDER_STATE_PAID', $order_state);
    }

    // plug module on the hook actionAdminControllerSetMedia
    $flag = $flag && $object->registerHook('actionAdminControllerSetMedia');

    // plug module on the hook displayAdminOrderMain
    $flag = $flag && $object->registerHook('displayAdminOrderMain');

    return $flag;
}
