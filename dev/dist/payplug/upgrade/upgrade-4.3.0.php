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

function upgrade_module_4_3_0()
{
    $payplug_amounts = Configuration::get('PAYPLUG_AMOUNTS');
    if ('{}' == $payplug_amounts) {
        $flag = Configuration::updateValue('PAYPLUG_AMOUNTS', '{"default":{"min":"EUR:99","max":"EUR:2000000"},"oney_x3_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x3_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_without_fees":{"min":"EUR:10000","max":"EUR:300000"}}');
    }

    $payplug_countries = Configuration::get('PAYPLUG_COUNTRIES');
    if ('{}' == $payplug_countries) {
        $flag = Configuration::updateValue('PAYPLUG_COUNTRIES', '{"oney_x3_with_fees":["YT","PF","MF","GF","FR","MQ","BL","NC","GP","RE"],"oney_x4_with_fees":["YT","PF","MF","GF","FR","MQ","BL","NC","GP","RE"],"oney_x3_without_fees":["YT","PF","MF","GF","FR","MQ","BL","NC","GP","RE"],"oney_x4_without_fees":["YT","PF","MF","GF","FR","MQ","BL","NC","GP","RE"]}');
    }

    return $flag;
}
