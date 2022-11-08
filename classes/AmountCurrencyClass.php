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

namespace PayPlug\classes;

class AmountCurrencyClass
{
    private $config;
    private $currency;
    private $dependencies;
    private $order;
    private $tools;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->currency = $this->dependencies->getPlugin()->getCurrency();
        $this->order = $this->dependencies->getPlugin()->getOrder();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
    }

    /**
     * Check if amount is correct
     *
     * @param object $cart
     *
     * @return bool
     */
    public function checkAmount($cart)
    {
        if (!$this->validate->validate('isLoadedObject', $cart)) {
            return false;
        }

        $currency = $this->currency->get((int) $cart->id_currency);
        if (!$this->validate->validate('isLoadedObject', $currency)) {
            return false;
        }

        $amounts_by_currency = $this->getAmountsByCurrency($currency->iso_code);
        $amount = $cart->getOrderTotal(true) * 100;

        if ($amount < $amounts_by_currency['min_amount'] || $amount > $amounts_by_currency['max_amount']) {
            return false;
        }

        return true;
    }

    /**
     * Check if amount is correct
     *
     * @param int    $amount
     * @param object $order
     *
     * @return bool
     */
    public function checkAmountPaidIsCorrect($amount, $order)
    {
        if (!$this->validate->validate('isLoadedObject', $order)) {
            return false;
        }

        $order_amount = $order->total_paid;

        if ($amount != 0) {
            return abs($order_amount - $amount) / $amount < 0.00001;
        }
        if ($order_amount != 0) {
            return abs($amount - $order_amount) / $order_amount < 0.00001;
        }

        return true;
    }

    /**
     * Check amount to refund
     *
     * @param int $amount
     *
     * @return string
     */
    public function checkAmountToRefund($amount)
    {
        return is_numeric($amount);
    }

    /**
     * check if currency is allowed
     *
     * @param object $cart
     *
     * @return bool
     */
    public function checkCurrency($cart)
    {
        if (!$this->validate->validate('isLoadedObject', $cart)) {
            return false;
        }

        $currency = $this->currency->get((int) ($cart->id_currency));
        if (!$this->validate->validate('isLoadedObject', $currency)) {
            return false;
        }

        if (!in_array($this->tools->tool('strtoupper', $currency->iso_code), $this->getSupportedCurrencies())) {
            return false;
        }

        return true;
    }

    /**
     * Format amount float to int or int to float
     *
     * @param $amount
     * @param bool $to_cents
     *
     * @return float|int
     */
    public function convertAmount($amount = 0, $to_cents = false)
    {
        if (!$amount) {
            return 0;
        }

        if ($to_cents) {
            return (float) ($amount / 100);
        }
        $amount = (float) ($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float) ($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/

            return (int) ($this->tools->tool('ps_round', $amount));
    }

    /**
     * Get amounts with the right currency
     *
     * @param string $iso_code
     *
     * @return array
     */
    public function getAmountsByCurrency($iso_code)
    {
        $min_amounts = [];
        $max_amounts = [];
        $min = $this->config->get(
            $this->dependencies->getConfigurationKey('minAmounts')
        );
        $max = $this->config->get(
            $this->dependencies->getConfigurationKey('maxAmounts')
        );
        foreach (explode(';', $this->tools->tool('strtoupper', $min)) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $min_amounts[$cur[1]] = (int) $cur[2];
        }
        foreach (explode(';', $this->tools->tool('strtoupper', $max)) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $max_amounts[$cur[1]] = (int) $cur[2];
        }
        $current_min_amount = $min_amounts[$this->tools->tool('strtoupper', $iso_code)];
        $current_max_amount = $max_amounts[$this->tools->tool('strtoupper', $iso_code)];

        return ['min_amount' => $current_min_amount, 'max_amount' => $current_max_amount];
    }

    /**
     * Get supported currencies
     *
     * @return array
     */
    private function getSupportedCurrencies()
    {
        $currencies = [];
        foreach (explode(';', $this->config->get(
            $this->dependencies->getConfigurationKey('minAmounts')
        )) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $currencies[] = $this->tools->tool('strtoupper', $cur[1]);
        }

        return $currencies;
    }
}
