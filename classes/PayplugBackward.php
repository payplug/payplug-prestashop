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

    public static function getConfiguration($key, $global = false)
    {
        if ($global) {
            $value = Configuration::getGlobalValue($key);
        } elseif (!$global) {
            //don't cast in int beacause Prestashop need to get null value for global settings
            $id_shop_group = Shop::getContextShopGroupID();
            //don't cast in int beacause Prestashop need to get null value for global settings
            $id_shop = Shop::getContextShopID();
            return Configuration::get($key, null, $id_shop_group, $id_shop);
        } else {
            $value = Configuration::get($key);
        }

        return $value;
    }

    public static function getMultipleConfiguration($keys)
    {
        /*
         * We do multiple calls to getConfiguration() to get recursive shop context
         * If this takes to many resources, it should be a good idea to merge calls in 3 requests instead.
         */

        $conf = [];
        foreach ($keys as $key) {
            $conf[$key] = PayplugBackward::getConfiguration($key);
        }

        return $conf;
    }

    public static function getHttpHost($http = false, $entities = false, $ignore_port = false)
    {
        return Tools::getHttpHost($http, $entities, $ignore_port);
    }

    public static function getModuleLink(
        $module,
        $controller = 'default',
        array $params = [],
        $ssl = null,
        $id_lang = null,
        $id_shop = null,
        $relative_protocol = false
    ) {
        $url = Context::getContext()->link->getModuleLink(
            $module,
            $controller,
            $params,
            $ssl,
            $id_lang,
            $id_shop,
            $relative_protocol
        );
        return $url;
    }

    /**
     * @param bool $id_gender
     * @param bool $id_lang
     * @return string|null
     */
    public static function getCustomerGender($id_gender = false, $id_lang = false)
    {
        if (!$id_gender) {
            return null;
        }
        if (!$id_lang) {
            $id_lang = (int)PayplugBackward::getConfiguration('PS_LANG_DEFAULT');
        }

        $gender = new Gender($id_gender, $id_lang);
        return Validate::isLoadedObject($gender) ? $gender->name : null;
    }

    public static function defineObjectModel(&$object, $props)
    {
        foreach ($props['fields'] as &$field) {
            switch ($field['type']) {
                case 'int':
                    $field['type'] = ObjectModel::TYPE_INT;
                    break;
                case 'bool':
                    $field['type'] = ObjectModel::TYPE_BOOL;
                    break;
                case 'float':
                    $field['type'] = ObjectModel::TYPE_FLOAT;
                    break;
                case 'date':
                    $field['type'] = ObjectModel::TYPE_DATE;
                    break;
                case 'html':
                    $field['type'] = ObjectModel::TYPE_HTML;
                    break;
                case 'nothing':
                    $field['type'] = ObjectModel::TYPE_NOTHING;
                    break;
                case 'sql':
                    $field['type'] = ObjectModel::TYPE_SQL;
                    break;
                case 'string':
                default:
                    $field['type'] = ObjectModel::TYPE_STRING;
                    break;
            }
        }
        $object::$definition = $props;
        return $object;
    }
}
