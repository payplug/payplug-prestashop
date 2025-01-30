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

function upgrade_module_4_8_0($object)
{
    $logger = $object->payplug_dependencies->getPlugin()->getLogger();
    $logger->addLog('Start upgrade script 4.8.0');

    $payment_methods = json_decode(Configuration::get('PAYPLUG_PAYMENT_METHODS'), true);
    $is_applepay = (bool) $payment_methods['applepay'];
    $flag = Configuration::updateValue('PAYPLUG_APPLEPAY_CARRIERS', '[]');
    $flag = $flag && Configuration::updateValue('PAYPLUG_APPLEPAY_CART', !$is_applepay);
    $flag = $flag && Configuration::updateValue('PAYPLUG_APPLEPAY_CHECKOUT', $is_applepay);

    $logger->addLog('End upgrade script 4.8.0, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
