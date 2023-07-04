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
        'inst' => (bool) Configuration::get('PAYPLUG_INST'),
        'mybank' => false,
        'one_click' => (bool) Configuration::get('PAYPLUG_ONE_CLICK'),
        'oney' => (bool) Configuration::get('PAYPLUG_ONEY'),
        'satispay' => false,
        'sofort' => false,
        'standard' => (bool) Configuration::get('PAYPLUG_STANDARD'),
    ];

    $flag = $flag && Configuration::updateValue('PAYPLUG_PAYMENT_METHODS', json_encode($payment_methods));
    $flag = $flag && Configuration::deleteByName('PAYPLUG_AMEX');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_APPLEPAY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_BANCONTACT');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_DEFERRED');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_INST');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONE_CLICK');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_ONEY');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_STANDARD');

    $flag && Configuration::updateValue('AMOUNTS', '{"oney_x3_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x3_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"bancontact":{"min":"EUR:100","max":"EUR:2000000"},"giropay":{"min":"EUR:100","max":"EUR:2000000"},"ideal":{"min":"EUR:100","max":"EUR:2000000"},"mybank":{"min":"EUR:100","max":"EUR:2000000"},"satispay":{"min":"EUR:100","max":"EUR:2000000"},"sofort":{"min":"EUR:100","max":"EUR:2000000"}}');
    $flag && Configuration::updateValue('COUNTRIES', '{"oney_x3_with_fees":["YT","BL","PF","GP","RE","MF","MQ","GF","FR","NC"],"oney_x4_with_fees":["YT","BL","PF","GP","RE","MF","MQ","GF","FR","NC"],"oney_x3_without_fees":["YT","BL","PF","GP","RE","MF","MQ","GF","FR","NC"],"oney_x4_without_fees":["YT","BL","PF","GP","RE","MF","MQ","GF","FR","NC"],"bancontact":["ALL"],"giropay":["DE"],"ideal":["NL"],"mybank":["IT"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"],"sofort":["AT","BE","DE","ES","IT","NL"]}');

    return $flag;
}
