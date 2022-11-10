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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PayPlug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_25_0($object)
{
    //we cannot allow 1.6 versions tu update from 1.7 content (and vice versa)
    if (version_compare(_PS_VERSION_, '1.7', '<')) {
        return true;
    }

    // Blank every CSV file if we don't have permission to "rm *"
    $csv_files = _PS_MODULE_DIR_ . $object->name . '/log/*.csv';
    foreach (glob($csv_files) as $path) {
        $file = fopen($path, 'w');
        ftruncate($file, 0);
        fclose($file);
    }

    Configuration::updateValue('PAYPLUG_DEBUG_MODE', 0);

    return true;
}
