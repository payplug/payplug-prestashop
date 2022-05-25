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

namespace PayPlugModule\classes;

use Cart;
use Configuration;
use Currency;
use Order;
use PayPlugModule\src\specific\ToolsSpecific;
use Tools;

class AmountCurrencyClass
{
    private $toolsSpecific;
    private $dependencies;

    public function __construct($toolsSpecific, $dependencies)
    {
        $this->toolsSpecific = $toolsSpecific;
        $this->dependencies = $dependencies;
    }

    /**
     * Check if amount is correct
     *
     * @param Cart $cart
     * @return bool
     */
    public function checkAmount($cart)
    {
        $currency = new Currency($cart->id_currency);
        $amounts_by_currency = $this->getAmountsByCurrency($currency->iso_code);
        $amount = $cart->getOrderTotal(true, Cart::BOTH) * 100;
        if ($amount < $amounts_by_currency['min_amount'] || $amount > $amounts_by_currency['max_amount']) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if amount is correct
     *
     * @param int $amount
     * @param Order $order
     * @return bool
     */
    public function checkAmountPaidIsCorrect($amount, $order)
    {
        $order_amount = $order->total_paid;

        if ($amount != 0) {
            return abs($order_amount - $amount) / $amount < 0.00001;
        } elseif ($order_amount != 0) {
            return abs($amount - $order_amount) / $order_amount < 0.00001;
        } else {
            return true;
        }
    }

    /**
     * Check amount to refund
     *
     * @param int $amount
     * @return string
     */
    public function checkAmountToRefund($amount)
    {
        return is_numeric($amount);
    }

//    /**
//     * check if currency is allowed
//     *
//     * @param Cart $cart
//     * @return bool
//     */
//    public function checkCurrency($cart)
//    {
//        $currency_order = new Currency((int)($cart->id_currency));
//        if (!in_array(\Tools::strtoupper($currency_order->iso_code), $this->getSupportedCurrencies())) {
//            return false;
//        }
//        return true;
//    }

//    /**
//     * Format amount float to int or int to float
//     *
//     * @param $amount
//     * @param bool $to_cents
//     * @return float|int
//     */
//    public function convertAmount($amount, $to_cents = false)
//    {
//        if ($to_cents) {
//            return (float)($amount / 100);
//        } else {
//            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
//            $amount = (float)($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
//            return (int)($this->toolsSpecific->tool('ps_round', $amount));
//        }
//    }

//    /**
//     * Get supported currencies
//     *
//     * @return array
//     */
//    private function getSupportedCurrencies($minAmountsConfig)
//    {
//        $currencies = [];
//        foreach (explode(';', $minAmountsConfig) as $amount_cur) {
//            $cur = [];
//            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
//            $currencies[] = Tools::strtoupper($cur[1]);
//        }
//
//        return $currencies;
//    }

    /**
     * Get amounts with the right currency
     *
     * @param string $iso_code
     * @return array
     */
    public function getAmountsByCurrency($iso_code)
    {
        $min_amounts = [];
        $max_amounts = [];
        $min = Configuration::get(
            $this->dependencies->getConfigurationKey('minAmounts')
        );
        $max = Configuration::get(
            $this->dependencies->getConfigurationKey('maxAmounts')
        );
        foreach (explode(';', Tools::strtoupper($min)) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $min_amounts[$cur[1]] = (int)$cur[2];
        }
        foreach (explode(';', Tools::strtoupper($max)) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $max_amounts[$cur[1]] = (int)$cur[2];
        }
        $current_min_amount = $min_amounts[Tools::strtoupper($iso_code)];
        $current_max_amount = $max_amounts[Tools::strtoupper($iso_code)];

        return ['min_amount' => $current_min_amount, 'max_amount' => $current_max_amount];
    }
}
