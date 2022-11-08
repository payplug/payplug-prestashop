<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PayPlug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_26_0()
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    $flag = true;

    //adding new configurations
    if (!Configuration::updateValue('PAYPLUG_ONEY_OPTIMIZED', 0)
        || !Configuration::updateValue('PAYPLUG_ONEY', 0)
        || !Configuration::updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', '')
        || !Configuration::updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', 'EUR:2000')
        || !Configuration::updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', 'EUR:150')
    ) {
        $flag = false;
    }

    return $flag;
}
