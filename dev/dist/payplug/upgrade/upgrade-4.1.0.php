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

function upgrade_module_4_1_0()
{
    $flag = true;

    $payment_methods = [
        'amex' => (bool) Configuration::get('PAYPLUG_AMEX'),
        'applepay' => (bool) Configuration::get('PAYPLUG_APPLEPAY'),
        'bancontact' => (bool) Configuration::get('PAYPLUG_BANCONTACT'),
        'deferred' => (bool) Configuration::get('PAYPLUG_DEFERRED'),
        'giropay' => false,
        'ideal' => false,
        'installment' => (bool) Configuration::get('PAYPLUG_INST'),
        'mybank' => false,
        'one_click' => (bool) Configuration::get('PAYPLUG_ONE_CLICK'),
        'oney' => (bool) Configuration::get('PAYPLUG_ONEY'),
        'satispay' => false,
        'sofort' => false,
        'standard' => (bool) Configuration::get('PAYPLUG_STANDARD'),
    ];

    $flag = $flag && Configuration::deleteByName('PAYPLUG_AMEX');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_APPLEPAY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_BANCONTACT');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_DEFERRED');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_INST');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONE_CLICK');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONEY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_STANDARD');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_MAX_AMOUNTS');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_MIN_AMOUNTS');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONEY_MAX_AMOUNTS');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONEY_MIN_AMOUNTS');

    $flag = $flag && Configuration::updateValue('PAYPLUG_PAYMENT_METHODS', json_encode($payment_methods));
    $flag = $flag && Configuration::updateValue('PAYPLUG_AMOUNTS', '{}');

    return $flag && Configuration::updateValue('PAYPLUG_COUNTRIES', '{}');
}
