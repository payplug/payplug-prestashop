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

function upgrade_module_2_22_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
    $flag = true;

    //adding new configurations
    if (!PayplugBackward::updateConfiguration('PAYPLUG_DEFERRED', 0)
        || !PayplugBackward::updateConfiguration('PAYPLUG_DEFERRED_AUTO', 0)
        || !PayplugBackward::updateConfiguration('PAYPLUG_DEFERRED_STATE', 0)
        || !PayplugBackward::updateConfiguration('PAYPLUG_ORDER_STATE_AUTH', 0)
        || !PayplugBackward::updateConfiguration('PAYPLUG_ORDER_STATE_AUTH_TEST', 0)
        || !PayplugBackward::updateConfiguration('PAYPLUG_ORDER_STATE_EXP', 0)
        || !PayplugBackward::updateConfiguration('PAYPLUG_ORDER_STATE_EXP_TEST', 0)) {
        $log->error('Fail to add new configuration');
        $flag = false;
    }

    //hooking
    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
        if (!$object->registerHook('actionOrderStatusUpdate')) {
            $log->error('Fail to hook');
            $flag = false;
        }
    } else {
        if (!$object->registerHook('updateOrderStatus')) {
            $log->error('Fail to hook');
            $flag = false;
        }
    }

    //add order-state for deferred payment
    if (!$object->createOrderStates()) {
        $log->error('Fail to add order state');
        $flag = false;
    }

    // Update payplug card table
    $sql_requests = array(
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` CHANGE `id_payplug_card` `position` INT(11) UNSIGNED NOT NULL',
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` DROP PRIMARY KEY',
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` DROP `position`',
        'ALTER TABLE `'._DB_PREFIX_.'payplug_card` ADD `id_payplug_card` INT(11) NOT NULL AUTO_INCREMENT FIRST, 
        ADD PRIMARY KEY (`id_payplug_card`)',
    );

    try {
        foreach ($sql_requests as $sql_request) {
            $request = Db::getInstance()->execute($sql_request);
            if (!$request) {
                $log->error('Fail to execute request: ' . $request);
                $flag = false;
            }
        }
    } catch (PrestaShopDatabaseException $e) {
        $log->error('Fail to execute requests');
        $flag = false;
    }

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        if (!PayplugBackward::updateConfiguration('PAYPLUG_VERSION_1_4', '2.22.0')) {
            $log->error('Fail to add new configuration');
            $flag = false;
        }
    }

    return $flag;
}
