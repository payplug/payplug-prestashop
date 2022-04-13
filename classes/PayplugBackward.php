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
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PayplugBackward
{
    public static function updateConfiguration($key, $value, $global = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=') && $global) {
            return Configuration::updateGlobalValue($key, $value);
        } elseif (version_compare(_PS_VERSION_, '1.5', '>=') && !$global) {
            //don't cast in int beacause Prestashop need to get null value for global settings
            $id_shop_group = Shop::getContextShopGroupID();
            //don't cast in int beacause Prestashop need to get null value for global settings
            $id_shop = Shop::getContextShopID();
            return Configuration::updateValue($key, $value, false, $id_shop_group, $id_shop);
        } else {
            return Configuration::updateValue($key, $value);
        }
    }
}
