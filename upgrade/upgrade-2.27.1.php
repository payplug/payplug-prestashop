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

require_once(_PS_MODULE_DIR_ . 'payplug/classes/MyLogPHP.class.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_27_1($object)
{
    $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
    $flag = true;

    // run the method who install Oney feature
    $flag = $object->installOney();

    //adding new configurations
    if (!Configuration::updateValue('PAYPLUG_ONEY_OPTIMIZED', 0)) {
        $log->error('Fail to add new configuration');
        $flag = false;
    }

    // Update payplug lock table
    $sql_requests = array(
        'ALTER TABLE `'._DB_PREFIX_.'payplug_lock` ADD CONSTRAINT lock_cart_unique UNIQUE (id_cart)',
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

    return $flag;
}
