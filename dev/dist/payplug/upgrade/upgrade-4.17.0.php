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

function upgrade_module_4_17_0($object)
{
    $flag = true;

    $logger = $object->module->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 4.17.0');

    $new_hooks = [
        'actionObjectOrderHistoryAddAfter',
    ];

    $old_hooks = [
        'actionOrderStatusUpdate',
    ];

    foreach ($new_hooks as $hook) {
        $is_register_in_hook = $object->isRegisteredInHook($hook);
        if (!$is_register_in_hook) {
            $flag = $flag && $object->registerHook($hook);
        }
    }

    foreach ($old_hooks as $hook) {
        $is_register_in_hook = $object->isRegisteredInHook($hook);
        if ($is_register_in_hook) {
            $flag = $flag && $object->unregisterHook($hook);
        }
    }

    $logger->addLog('End upgrade script 4.17.0, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
