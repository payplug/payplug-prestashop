<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_26_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '>=')) {
        return true;
    }

    $flag = true;

    if (!$object->checkVersion()) {
        return $flag;
    }

    // run the method who install Oney feature
    $flag = $object->installOney();

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        if (!PayplugBackward::updateConfiguration('PAYPLUG_VERSION_1_4', '2.26.0')) {
            $flag = false;
        }
    }

    //adding new configurations
    if (!PayplugBackward::updateConfiguration('PAYPLUG_ONEY_OPTIMIZED', 0)) {
        $flag = false;
    }

    return $flag;
}