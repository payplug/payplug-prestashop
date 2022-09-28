<?php

/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\utilities\helpers;

use PayPlug\classes\DependenciesClass;

class UrlHelper
{
    public static function register()
    {
        // Check if environnement is valid
        if (!preg_match('/http(s?):\/\/api(-qa\.payplug|\.notpayplug).(com|test)/', $_ENV['API_BASE_URL'])) {
            return false;
        }

        $dependencies = new DependenciesClass();

        $constant = $dependencies->getPlugin()->getConstant();
        $contextAdapter = $dependencies->getPlugin()->getContext();
        $query = $dependencies->getPlugin()->getQuery();
        $shopAdapter = $dependencies->getPlugin()->getShop();
        $toolsAdapter = $dependencies->getPlugin()->getTools();
        $validateAdapter = $dependencies->getPlugin()->getValidate();

        $params = $toolsAdapter->getAllValues();
        $url = $shopAdapter->getBaseURL($contextAdapter->get()->shop->id, true) . 'index.php';
        $step = 0;
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // todo: handle value as array
                continue;
            }
            $url .= ($step ? '&' : '?') . $key . '=' . $value;
            $step++;
        }

        if (!$validateAdapter->validate('isUrl', $url)) {
            return false;
        }

        $key = $query->escape(md5($url));
        $query
            ->insert()
            ->into($constant->get('_DB_PREFIX_') . $dependencies->name . '_url')
            ->fields('`key`')->values($query->escape($key))
            ->fields('`url`')->values($query->escape($url));

        return $query->build();
    }

    public static function getAll()
    {
        // Check if environnement is valid
        if (!preg_match('/http(s?):\/\/api(-qa\.payplug|\.notpayplug).(com|test)/', $_ENV['API_BASE_URL'])) {
            return false;
        }

        $dependencies = new DependenciesClass();
        $constant = $dependencies->getPlugin()->getConstant();
        $query = $dependencies->getPlugin()->getQuery();
        $routes = $query
            ->select()
            ->fields('`url`, `key`')
            ->from($constant->get('_DB_PREFIX_') . $dependencies->name . '_url')
            ->build();

        return $routes;
    }
}
