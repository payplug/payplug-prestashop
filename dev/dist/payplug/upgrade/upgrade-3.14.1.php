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

function upgrade_module_3_14_1()
{
    $flag = true;

    // Update payplug configuration PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS and PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS
    $flag = $flag && Configuration::updateValue(
        'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS',
        'EUR:300000'
    );

    return $flag && Configuration::updateValue(
        'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS',
        'EUR:10000'
    );
}
