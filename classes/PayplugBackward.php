<?php
/**
 * 2013 - 2016 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_.'payplug/classes/PayplugTools.php');

class PayplugBackward
{
    public static function updateConfiguration($key, $value, $global = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=') && $global) {
            return Configuration::updateGlobalValue($key, $value);
        } elseif (version_compare(_PS_VERSION_, '1.5', '>=') && !$global) {
            $id_shop_group = (int)Shop::getContextShopGroupID();
            $id_shop = (int)Shop::getContextShopID();
            return Configuration::updateValue($key, $value, false, $id_shop_group, $id_shop);
        } else {
            return Configuration::updateValue($key, $value);
        }
    }

    public static function getConfiguration($key, $global = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=') && $global) {
            $value = Configuration::getGlobalValue($key);
        } elseif (version_compare(_PS_VERSION_, '1.5', '>=') && !$global) {
            $id_shop_group = (int)Shop::getContextShopGroupID();
            $id_shop = (int)Shop::getContextShopID();
            return Configuration::get($key, null, $id_shop_group, $id_shop);
        } else {
            $value = Configuration::get($key);
        }

        return $value;
    }

    public static function fileGetContents($file)
    {
        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            //$content = file_get_contents($file);
        } else {
            $content = Tools::file_get_contents($file);
        }
        return $content;
    }

    public static function jsonEncode($content)
    {
        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            //$json_content = json_encode($content);
        } else {
            $json_content = Tools::jsonEncode($content);
        }
        return $json_content;
    }

    public static function jsonDecode($json_content)
    {
        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            //$content = json_decode($json_content);
        } else {
            $content = Tools::jsonDecode($json_content);
        }
        return $content;
    }

    public static function strlen($str)
    {
        if (version_compare(_PS_VERSION_, '1.2', '<')) {
            //$lenght = strlen($str);
        } else {
            $lenght = Tools::strlen($str);
        }
        return $lenght;
    }

    public static function substr($str, $a, $b)
    {
        if (version_compare(_PS_VERSION_, '1.2', '<')) {
            //$res = substr($str, $a, $b);
        } else {
            $res = Tools::substr($str, $a, $b);
        }
        return $res;
    }

    public static function strtolower($str)
    {
        if (version_compare(_PS_VERSION_, '1.2', '<')) {
            //$content = strtolower($str);
        } else {
            $content = Tools::strtolower($str);
        }
        return $content;
    }

    public static function getHttpHost($http = false, $entities = false, $ignore_port = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $host = Tools::getHttpHost($http, $entities);
        } else {
            $host = Tools::getHttpHost($http, $entities, $ignore_port);
        }
        return $host;
    }
}
