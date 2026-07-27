<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\utilities\helpers;

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmountHelper
{
    private $dependencies;

    public function __construct($dependencies = null)
    {
        $this->dependencies = $dependencies ?? new DependenciesClass();
    }

    /**
     * @description Format amount float to int or int to float.
     *
     * @param $amount
     * @param bool $from_cents
     *
     * @return float|int
     */
    public function convertAmount($amount = 0, $from_cents = false)
    {
        if (!$amount) {
            return 0;
        }

        if ($from_cents) {
            return \PayplugUnifiedCore\Utilities\Helpers\AmountHelper::fromCents((int) $amount);
        }

        $round_mode = (int) $this->dependencies->getPlugin()->getConfigurationClass()->getValue('PS_PRICE_ROUND_MODE');
        $constantAdapter = $this->dependencies->getPlugin()->getConstant();
        $roundUp = $constantAdapter->get('PS_ROUND_UP');
        $roundDown = $constantAdapter->get('PS_ROUND_DOWN');
        $roundHalfUp = $constantAdapter->get('PS_ROUND_HALF_UP');
        $roundHalfDown = $constantAdapter->get('PS_ROUND_HALF_DOWN');
        $roundHalfEven = $constantAdapter->get('PS_ROUND_HALF_EVEN');
        $roundHalfOdd = $constantAdapter->get('PS_ROUND_HALF_ODD');
        $modes = [
            $roundHalfUp => PHP_ROUND_HALF_UP,
            $roundHalfDown => PHP_ROUND_HALF_DOWN,
            $roundHalfEven => PHP_ROUND_HALF_EVEN,
            $roundHalfOdd => PHP_ROUND_HALF_ODD,
        ];
        $mode = isset($modes[$round_mode]) ? $modes[$round_mode] : PHP_ROUND_HALF_UP;

        switch ($round_mode) {
            case $roundUp:
            case $roundDown:
                $amount = $amount * 100;
                $amount_to_cent = $this->dependencies->getPlugin()->getTools()->tool('ps_round', $amount);

                break;

            case $roundHalfUp:
            case $roundHalfDown:
            case $roundHalfEven:
            case $roundHalfOdd:
            default:
                $amount_to_cent = \PayplugUnifiedCore\Utilities\Helpers\AmountHelper::toCents((float) $amount, $mode);
        }

        return (int) $amount_to_cent;
    }

    /**
     * @description Format the Oney thresholds amount
     *
     * @param $amount
     *
     * @return array
     */
    public function formatOneyAmount($amount)
    {
        if (!is_int($amount)) {
            return [
                'result' => false,
                'message' => '$amount must be a int type',
            ];
        }

        return [
            'result' => (float) $this->convertAmount($amount, true),
            'message' => '$amount is formatted',
        ];
    }

    /**
     * @description Check is given amount is valid with price limit
     *
     * @param array $price_limit
     * @param float $amount
     *
     * @return array
     */
    public function validateAmount($price_limit = [], $amount = 0)
    {
        if (!is_array($price_limit) || empty($price_limit)) {
            return [
                'result' => false,
                'message' => 'Wrong paramaters given, $price_limit must be a non empty array',
            ];
        }

        if (!is_float($amount)) {
            return [
                'result' => false,
                'message' => 'Wrong paramaters given, $amount must be a non null float',
            ];
        }

        $min = explode(':', $price_limit['min']);
        $min_amount = isset($min[1]) ? $min[1] : null;

        $max = explode(':', $price_limit['max']);
        $max_amount = isset($max[1]) ? $max[1] : null;

        $formated_amount = $this->convertAmount($amount);

        if ($formated_amount < $min_amount) {
            return [
                'result' => false,
                'message' => 'Given $amount is lower than expected',
            ];
        }

        if ($formated_amount > $max_amount) {
            return [
                'result' => false,
                'message' => 'Given $amount is higher than expected',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }
}
