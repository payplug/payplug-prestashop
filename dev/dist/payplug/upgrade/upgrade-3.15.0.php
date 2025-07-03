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

function upgrade_module_3_15_0()
{
    // Delete publishable key usage, no longer needed for integrated payment
    $flag = Configuration::deleteByName('PAYPLUG_PUBLISHABLE_KEY_TEST');
    $flag = $flag && Configuration::deleteByName('PAYPLUG_PUBLISHABLE_KEY');
    // update PAYPLUG_ONEY_FEES value to fix SMP-1390
    $flag = $flag && Configuration::updateValue('PAYPLUG_ONEY_FEES', 1);

    // Create new configuration variable for the merchant onboarding
    return $flag && Configuration::updateValue('PAYPLUG_ONBOARDING_STATES', '{}');
}
