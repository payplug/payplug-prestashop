<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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
        'standard' => Configuration::get('PAYPLUG_STANDARD'),
        'one_click' => Configuration::get('PAYPLUG_ONE_CLICK'),
        'inst' => Configuration::get('PAYPLUG_INST'),
        'deferred' => Configuration::get('PAYPLUG_DEFERRED'),
        'oney' => Configuration::get('PAYPLUG_ONEY'),
        'bancontact' => Configuration::get('PAYPLUG_BANCONTACT'),
        'applepay' => Configuration::get('PAYPLUG_APPLEPAY'),
        'amex' => Configuration::get('PAYPLUG_AMEX'),
        'satispay' => Configuration::get('PAYPLUG_SATISPAY'),
        'sofort' => Configuration::get('PAYPLUG_SOFORT'),
        'giropay' => Configuration::get('PAYPLUG_GIROPAY'),
        'ideal' => Configuration::get('PAYPLUG_IDEAL'),
        'mybank' => Configuration::get('PAYPLUG_MYBANK'),
    ];

    $flag = $flag && Configuration::updateValue('PAYPLUG_PAYMENT_METHODS', json_encode($payment_methods));

    $flag = $flag && Configuration::deleteByName('PAYPLUG_STANDARD');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONE_CLICK');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_INST');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_DEFERRED');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONEY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_BANCONTACT');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_APPLEPAY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_AMEX');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_SATISPAY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_SOFORT');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_GIROPAY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_IDEAL');

    return $flag && Configuration::deleteByName('PAYPLUG_MYBANK');
}
