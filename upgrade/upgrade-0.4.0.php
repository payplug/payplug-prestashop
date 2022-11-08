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

function upgrade_module_0_4_0()
{
    $flag = true;
    if (Configuration::get('PSPAYLATER_ONEY_ALLOWED_COUNTRIES') != null
        && in_array('BE', explode(',', Configuration::get('PSPAYLATER_ONEY_ALLOWED_COUNTRIES')))) {
        // set Oney advanced Options to 0 for belgian merchants of the pspaylater module.
        $flag = $flag && Configuration::updateValue('PSPAYLATER_ONEY_CART_CTA', 0)
            && Configuration::updateValue(
                'PSPAYLATER_ONEY_PRODUCT_CTA',
                0
            );
    }

    return $flag;
}
