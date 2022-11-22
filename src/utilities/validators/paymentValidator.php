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
    /**
     * @description  check if payment can be captured
     *
     * @param $payment
     * @param $is_oney
     *
     * @return array
     */
    public function canBeCaptured($payment, $is_oney)
    {
        if (empty($payment) || !is_object($payment)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $payment must be a non empty object.',
            ];
        }
        if (!is_bool($is_oney)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $is_oney must be a boolean type.',
            ];
        }

        if (isset($payment->authorization) && $payment->authorization !== null && !$is_oney) {
            if (!$payment->is_paid) {
                if (isset($payment->authorization->expires_at) && $payment->authorization->expires_at - time() > 0) {
                    if (isset($payment->failure) && $payment->failure) {
                        return [
                            'result' => false,
                            'message' => 'Payment in failure, can not be be captured.',
                        ];
                    }

                    return [
                        'result' => true,
                        'message' => 'Payment can be captured.',
                    ];
                }
            }
        }

        return [
            'result' => false,
            'message' => 'Payment can not be captured.',
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
}
