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

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmountHelper
{
    private $dependencies;

    public function __construct($dependencies = null)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Format amount float to int or int to float.
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

        $amount = $this->dependencies->getPlugin()->getTools()->tool('ps_round', $amount);

        return intval(strval($amount));
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
            'result' => $amount / 100,
            'message' => '$amount is formatted',
        ];
    }

    /**
     * @description Check is given amount is valid with price limit
     *
     * @param array $price_limit
     * @param int $amount
     *
     * @return array
     */
    public function isValidAmount($price_limit = [], $amount = 0)
    {
        if (!is_array($price_limit) || empty($price_limit)) {
            return [
                'result' => false,
                'message' => 'Wrong paramaters given, $price_limit must be a non empty array',
            ];
        }

        if (!is_float($amount) || !$amount) {
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
