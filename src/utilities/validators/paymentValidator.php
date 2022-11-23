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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\utilities\validators;

class paymentValidator
{
    private $uncancellable_payment_method = ['oneclick', 'bancontact', 'apple_pay', 'oney', 'amex'];

    /**
     * @description  check if payment can be captured
     *
     * @param object $payment
     *
     * @return array
     */
    public function canBeCaptured($payment)
    {
        if (empty($payment) || !is_object($payment)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $payment must be a non empty object.',
            ];
        }

        if (!isset($payment->authorization) || !$payment->authorization) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment does not contain authorization',
            ];
        }

        if (!isset($payment->authorization->expires_at) || !$payment->authorization->expires_at) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment->authorization->expires_at should be defined',
            ];
        }

        if (isset($payment->failure) && $payment->failure) {
            return [
                'result' => false,
                'message' => 'Payment in failure, can not be be captured.',
            ];
        }

        if ($payment->is_paid) {
            return [
                'result' => false,
                'message' => 'The given payment resource is already captured',
            ];
        }

        if ($payment->authorization->expires_at - time() < 0) {
            return [
                'result' => false,
                'message' => 'The payment capture is expired',
            ];
        }

        return [
            'result' => true,
            'message' => 'Payment can be captured.',
        ];
    }

    /**
     * @description check if the payment is refundable
     *
     * @param $pay_id
     * @param $data
     * @param false $truly_refundable_amount
     * @param false $total_amount
     *
     * @return array
     */
    public function canBeRefund($pay_id, $data, $truly_refundable_amount = false, $total_amount = false)
    {
        if ($pay_id == null) {
            if (!is_numeric($total_amount)) {
                return [
                    'result' => false,
                    'message' => 'invalid argument, $total_amount must be a numeric type.',
                ];
            }
            if (!is_numeric($truly_refundable_amount)) {
                return [
                    'result' => false,
                    'message' => 'invalid argument, $truly_refundable_amount must be a numeric type.',
                ];
            }

            if ($truly_refundable_amount < $total_amount) {
                return [
                    'result' => false,
                    'message' => '$truly_refundable_amount must be lower than $total_amount.',
                ];
            }
            if (empty($data) || !is_array($data)) {
                return [
                    'result' => false,
                    'message' => '$data must not be empty.',
                ];
            }

            return [
                'result' => true,
                'message' => 'payment can be refund.',
            ];
        } elseif (!is_string($pay_id) || empty($pay_id)) {
            return [
                'result' => false,
                'message' => 'invalid argument, $pay_id must be a string.',
            ];
        }
        if (empty($data) || !is_array($data)) {
            return [
                'result' => false,
                'message' => '$data must be a non empty array.',
            ];
        }

        return [
            'result' => true,
            'message' => 'payment can be refund.',
        ];
    }

    /**
     * @description check if the payment creation went well
     *
     * @param $payment
     *
     * @return array
     */
    public function hasError($payment)
    {
        if (!isset($payment) || !is_array($payment) || empty($payment)) {
            return [
                'result' => true,
                'message' => '$payment must be a non empty array.',
            ];
        }
        if (!is_bool($payment['result']) || !isset($payment['result'])) {
            return [
                'result' => true,
                'message' => 'result argument inside payment array must be a non empty boolean.',
            ];
        }
        if (!$payment['result']) {
            return [
                'result' => true,
                'message' => '$payment is failed.',
            ];
        }

        return [
            'result' => false,
            'message' => '$payment is succeeded.',
        ];
    }

    /**
     * @description check if a given feature is permitted
     *
     * @param $permissions
     * @param $feature
     *
     * @return array
     */
    public function hasPermissions($permissions, $feature)
    {
        if (!isset($permissions) || !is_array($permissions) || empty($permissions)) {
            return [
                'result' => false,
                'message' => 'invalid argument, $permissions must be a non empty array.',
            ];
        }
        if (!isset($feature) || !is_string($feature) || empty($feature)) {
            return [
                'result' => false,
                'message' => 'invalid argument, $feature must be a non empty string.',
            ];
        }
        if (!array_key_exists($feature, $permissions) || empty($permissions[$feature])) {
            return [
                'result' => false,
                'message' => '$feature is not findable in the array.',
            ];
        }

        return ['result' => $permissions[$feature], 'message' => 'success'];
    }

    /**
     * @description  check if the country is allowed
     *
     * @param string $allowedCountries
     * @param string $country
     *
     * @return array
     */
    public function isAllowedCountry($allowedCountries = '', $country = '')
    {
        if (!is_string($allowedCountries) || !$allowedCountries) {
            return [
                'result' => false,
                'message' => 'Invalid allowed countries format',
            ];
        }
        if (!is_string($country) || !$country) {
            return [
                'result' => false,
                'message' => 'Invalid country format',
            ];
        }

        return [
            'result' => count($allowedCountries) > 1 ? in_array($country, explode(',', $allowedCountries)) : $country == $allowedCountries,
            'message' => 'Success',
        ];
    }

    /**
     * @description Check if given amount is valid with the given limits
     *
     * @param int   $amount
     * @param array $limits
     *
     * @return array
     */
    public function isAmount($amount = 0, $limits = [])
    {
        if (!is_int($amount) || !$amount) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $amount must be a non null integer',
            ];
        }
        if (!is_array($limits) || empty($limits)) {
            return [
                'result' => false,
                'message' => 'Invalid argument $limits, $amount must be a non empty array',
            ];
        }
        if (!isset($limits['min'])) {
            return [
                'result' => false,
                'message' => 'Missing array key: $limits[min]',
            ];
        }
        if (!is_int($limits['min'])) {
            return [
                'result' => false,
                'message' => 'Wrong array value, $limits[min] must be an integer',
            ];
        }
        if (!isset($limits['max'])) {
            return [
                'result' => false,
                'message' => 'Missing array key: $limits[max]',
            ];
        }
        if (!is_int($limits['max'])) {
            return [
                'result' => false,
                'message' => 'Wrong array value, $limits[max] must be an integer',
            ];
        }
        if ($amount < $limits['min'] || $limits['max'] < $amount) {
            return [
                'result' => false,
                'message' => 'Wrong amount given: ' . $amount
                    . ', $amount must be between ' . $limits['min']
                    . ' and ' . $limits['max'],
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if a payment hash match with the store one
     *
     * @param string $hash
     * @param string $stored_hash
     *
     * @return array
     */
    public function isCachedPayment($hash = '', $stored_hash = '')
    {
        if (!is_string($hash) || !$hash) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $hash must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[a-z0-9]{64}$/', $hash)) {
            return [
                'result' => false,
                'message' => 'Invalid hash format given, $hash given is not valid',
            ];
        }

        if (!is_string($stored_hash) || !$stored_hash) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $stored_hash must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[a-z0-9]{64}$/', $stored_hash)) {
            return [
                'result' => false,
                'message' => 'Invalid hash format given, $stored_hash given is not valid',
            ];
        }

        if ($stored_hash != $hash) {
            return [
                'result' => false,
                'message' => 'The given hash does not match with the stored one',
            ];
        }

        return [
            'result' => true,
            'message' => 'The given hash match with the stored one',
        ];
    }

    /**
     * @description Check if given date is timeout to use a store payment
     *
     * @param string $date
     *
     * @return array
     */
    public function isTimeoutCachedPayment($date = '')
    {
        if (!is_string($date) || !$date) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $date must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}\ [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $date)) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $date must be a date in format Y-m-d H:i:s',
            ];
        }

        $given_date = date('Y-m-d H:i:s', strtotime($date));
        $limits_date = date('Y-m-d H:i:s', strtotime('-3 minutes'));

        if ($given_date < $limits_date) {
            return [
                'result' => false,
                'message' => 'Given date is timeout',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given payment is cancellable
     *
     * @param string $payment_method
     *
     * @return array
     */
    public function isCancellable($payment_method = '')
    {
        if (!is_string($payment_method) || !$payment_method) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $payment_method must be a non empty string',
            ];
        }

        if (in_array($payment_method, $this->uncancellable_payment_method)) {
            return [
                'result' => false,
                'message' => 'Given payment method is not cancellable.',
            ];
        }

        return [
            'result' => true,
            'message' => 'Given payment method is cancellable.',
        ];
    }

    /**
     * @description Check if given resource is deferred
     *
     * @param object $payment
     *
     * @return array
     */
    public function isDeferred($payment = null)
    {
        if (!is_object($payment) || !$payment) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $payment must be a non empty object',
            ];
        }

        $oney_payment_method = [
            'oney_x3_with_fees',
            'oney_x4_with_fees',
            'oney_x3_without_fees',
            'oney_x4_without_fees',
        ];
        if (isset($payment->payment_method, $payment->payment_method['type']) && in_array($payment->payment_method['type'], $oney_payment_method)) {
            return [
                'result' => false,
                'message' => 'Given $payment is created with oney, it cannot be deferred',
            ];
        }

        if (!isset($payment->authorization) || !$payment->authorization) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment does not contain authorization',
            ];
        }

        if (!isset($payment->authorization->authorized_at) || !$payment->authorization->authorized_at) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment->authorization->authorized_at should be defined',
            ];
        }

        return [
            'result' => true,
            'message' => 'Current ressource is a deferred payment',
        ];
    }
}
