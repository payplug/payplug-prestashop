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

namespace PayPlugModule\src\utilities\helpers;

use PayPlugModule\src\utilities\helpers\ToolsHelper;
use PayPlugModule\src\specific\CurrencySpecific;
use PayPlugModule\src\utilities\adapter\CurrencyAdapter;

class CurrencyHelper
{
    /**
     * @description Format amount float to int or int to float
     *
     * @param $amount
     * @param false $to_cents
     * @return float|int
     */
    public static function convertAmount($amount = false, $to_cents = false)
    {
        if (!$amount || !is_numeric($amount)) {
            return false;
        }

        if ($to_cents) {
            return (float)($amount / 100);
        } else {
            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float)($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
            return (int)(ToolsHelper::tool('ps_round', $amount));
        }
    }

    /**
     * @description Get supported currencies
     *
     * @param $minAmountsConfig
     * @return array
     */
    private static function getSupportedCurrencies($minAmountsConfig = '')
    {
        $currencies = [];

        if (!$minAmountsConfig || !is_string($minAmountsConfig)) {
            return $currencies;
        }

        foreach (explode(';', $minAmountsConfig) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $currencies[] = ToolsHelper::tool('strtoupper', $cur[1]);
        }

        return $currencies;
    }

    /**
     * @description Check if currency is allowed
     *
     * @param object $cart
     * @param string $minAmountsConfig
     * @return bool
     */
    public static function checkCurrency($cart = false, $minAmountsConfig = '')
    {
        if (!is_object($cart) || !$cart->id) {
            return false;
        }

        if (!$minAmountsConfig || !is_string($minAmountsConfig)) {
            return false;
        }

        $currency_order = CurrencyAdapter::get((int)($cart->id_currency));
        $currencies = self::getSupportedCurrencies($minAmountsConfig);
        if (!in_array(
            ToolsHelper::tool('strtoupper', $currency_order->iso_code),
            $currencies
        )) {
            return false;
        }
        return true;
    }

    /**
     * @description Check if given amount is valid
     *
     * @param false $amount
     * @return bool
     */
    public static function isValidAmount($amount = false)
    {
        return is_numeric($amount);
    }
}
