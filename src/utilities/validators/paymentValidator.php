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

namespace PayPlug\src\utilities\validators;

use libphonenumberlight;

if (!defined('_PS_VERSION_')) {
    exit;
}

class paymentValidator
{
    private $uncancellable_payment_method = [
        'amex',
        'bancontact',
        'integrated',
        'oneclick',
        'oney',
    ];

    /**
     * @description Check if the payment is refundable
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
        if (null == $pay_id) {
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
     * @description Check if given resource can save a card
     *
     * @param null $payment
     * @param mixed|null $resource
     *
     * @return bool
     */
    public function canSaveCard($resource = null)
    {
        if (!is_object($resource) || !$resource) {
            return false;
        }

        $can_save_card = false === \strpos($resource->id, 'inst')
            && (!isset($resource->installment_plan_id) || !$resource->installment_plan_id);

        return $can_save_card && (
            $resource->save_card
                || (
                    $resource->card->id
                    && (
                        $resource->hosted_payment
                        || 'INTEGRATED_PAYMENT' == $resource->integration
                    )
                )
        );
    }

    /**
     * @description Check if the payment creation went well
     * todo: this method should check if there is an error in the payment resource nor in his creation (already sending a result)
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
     * @description Check if a given feature is permitted
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
     * @description Check if the country is allowed
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

        $countries = explode(',', $allowedCountries);
        if (!in_array($country, $countries)) {
            return [
                'result' => false,
                'message' => 'Given country does not match with the list',
            ];
        }

        return [
            'result' => true,
            'message' => 'Success',
        ];
    }

    /**
     * @description Check if given amount is valid with the given limits
     *
     * @param int $amount
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
     * @description Check if given domain is allowed
     *
     * @param string $domain
     * @param array $allowed_domains
     *
     * @return array
     */
    public function isApplepayAllowedDomain($domain = '', $allowed_domains = [])
    {
        if (!is_string($domain) || !$domain) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $domain must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[:_\.\-\/a-zA-Z0-9-]+\.[a-zA-Z]{2,}$/', $domain)) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $domain must be a valid domain format',
            ];
        }

        if (!is_array($allowed_domains) || !$allowed_domains) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $allowed_domains must be a non empty array',
            ];
        }

        if (!in_array($domain, $allowed_domains)) {
            return [
                'result' => false,
                'message' => 'Given domain does not match with the list',
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
     * @description Check if given iso code match with the allowed list
     *
     * @param string $currency
     * @param array $currencies
     *
     * @return array
     */
    public function isCurrency($currency = '', $currencies = [])
    {
        if (!is_string($currency) || !$currency) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $currency must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[a-zA-Z]{3}$/', $currency)) {
            return [
                'result' => false,
                'message' => 'Invalid iso code format given, $currency given is not valid',
            ];
        }

        if (!is_array($currencies) || !$currencies) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $currencies must be a non empty array',
            ];
        }

        foreach ($currencies as $curr) {
            if (!(bool) preg_match('/^[a-zA-Z]{3}$/', $curr)) {
                return [
                    'result' => false,
                    'message' => 'Invalid iso code format given in array, $currency given is not valid',
                ];
            }
        }

        if (!in_array($currency, $currencies)) {
            return [
                'result' => false,
                'message' => 'Given currency does not match with the list',
            ];
        }

        return [
            'result' => true,
            'message' => '',
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

        if (!isset($payment->authorization->expires_at) || !$payment->authorization->expires_at) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment->authorization->expires_at should be defined',
            ];
        }

        if (!isset($payment->authorization->authorized_amount) || !$payment->authorization->authorized_amount) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment->authorization->authorized_amount should be defined',
            ];
        }

        return [
            'result' => true,
            'message' => 'Current ressource is a deferred payment',
        ];
    }

    /**
     * @description Check if the amount is between min and max for the given payment method
     *
     * @param $amount
     * @param $payment_method
     * @param $payment_methods_amount
     *
     * @return array
     */
    public function isEligibleByAmount($amount, $payment_method, $payment_methods_amount)
    {
        if (!is_int($amount)) {
            return [
                'result' => false,
                'message' => '$amount must be a int type',
            ];
        }

        if (!is_string($payment_method)) {
            return [
                'result' => false,
                'message' => '$payment_method must be a string type',
            ];
        }

        if (!is_string($payment_methods_amount)) {
            return [
                'result' => false,
                'message' => '$payment_methods_amount must be a string type',
            ];
        }

        $payment_methods_amount = json_decode($payment_methods_amount);

        switch ($payment_method) {
            case 'giropay':
                $payment = 'giropay';

                break;

            case 'ideal':
                $payment = 'ideal';

                break;

            case 'mybank':
                $payment = 'mybank';

                break;

            case 'oney':
                $payment = 'oney';

                break;

            case 'satispay':
                $payment = 'satispay';

                break;

            case 'sofort':
                $payment = 'sofort';

                break;

            default:
                $payment = 'default';

                break;
        }

        if ($amount < (int) str_replace('EUR:', '', $payment_methods_amount->{$payment}->min)
            || $amount > (int) str_replace('EUR:', '', $payment_methods_amount->{$payment}->max)) {
            return [
                'result' => false,
                'message' => $amount . ' is not eligible for ' . $payment_method,
            ];
        }

        return [
            'result' => true,
            'message' => $amount . ' is eligible for ' . $payment_method,
        ];
    }

    /**
     * @description Check if the amount is between min and max for the given payment method
     *
     * @param $country
     * @param $payment_method
     * @param $payment_methods_countries
     *
     * @return array
     */
    public function isEligibleByCountry($country, $payment_method, $payment_methods_countries)
    {
        if (!is_string($country)) {
            return [
                'result' => false,
                'message' => '$country must be a string type',
            ];
        }

        if (!is_string($payment_method)) {
            return [
                'result' => false,
                'message' => '$payment_method must be a string type',
            ];
        }

        if (!is_string($payment_methods_countries)) {
            return [
                'result' => false,
                'message' => '$payment_methods_countries must be a string type',
            ];
        }

        $payment_methods_countries = json_decode($payment_methods_countries);

        switch ($payment_method) {
            case 'giropay':
                $payment = 'giropay';

                break;

            case 'ideal':
                $payment = 'ideal';

                break;

            case 'mybank':
                $payment = 'mybank';

                break;

            case 'oney':
                $payment = 'oney';

                break;

            case 'satispay':
                $payment = 'satispay';

                break;

            case 'sofort':
                $payment = 'sofort';

                break;

            default:
                $payment = 'default';

                break;
        }

        if (!in_array($country, $payment_methods_countries->{$payment})) {
            return [
                'result' => false,
                'message' => $country . ' is not eligible for ' . $payment_method,
            ];
        }

        return [
            'result' => true,
            'message' => $country . ' is eligible for ' . $payment_method,
        ];
    }

    /**
     * @description Check if payment is expired
     *
     * @param object $payment
     *
     * @return array
     */
    public function isExpired($payment)
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

        if (time() < $payment->authorization->expires_at) {
            return [
                'result' => false,
                'message' => 'The payment capture not is expired',
            ];
        }

        return [
            'result' => true,
            'message' => 'Payment is expired',
        ];
    }

    /**
     * @description Check if given payment has failure
     *
     * @param null $payment
     *
     * @return array
     */
    public function isFailed($payment = null)
    {
        if (!is_object($payment) || !$payment) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $payment must be a non empty object',
            ];
        }

        if (!isset($payment->failure)) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment does not contain failure props',
            ];
        }

        if (!$payment->failure) {
            return [
                'result' => false,
                'message' => 'Payment does not contain failure',
            ];
        }

        if (!isset($payment->failure->message)) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment failure does not contain message props',
            ];
        }

        if (!isset($payment->failure->code)) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment failure does not contain code props',
            ];
        }

        return [
            'result' => true,
            'message' => $payment->failure->message,
        ];
    }

    /**
     * @description Check if given payment id is installment
     *
     * @param string $payment_id
     *
     * @return array
     */
    public function isInstallment($payment_id = '')
    {
        if (!is_string($payment_id) || !$payment_id) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $payment_id must be a non empty string',
            ];
        }

        if (false === \strpos($payment_id, 'inst')) {
            return [
                'result' => false,
                'message' => 'Given payment id is not from installment payment',
            ];
        }

        return [
            'result' => true,
            'message' => 'Given payment id is from installment payment',
        ];
    }

    /**
     * @description Check if given iso code are valid
     *
     * @param string $shipping_country_iso
     * @param string $billing_country_iso
     *
     * @return array
     */
    public function isOneyCountry($shipping_country_iso = '', $billing_country_iso = '')
    {
        if (!is_string($shipping_country_iso) || !$shipping_country_iso) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $shipping_country_iso must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[a-zA-Z]{2}$/', $shipping_country_iso)) {
            return [
                'result' => false,
                'message' => 'Invalid argument format given, $shipping_country_iso not a valid iso code',
            ];
        }

        if (!is_string($billing_country_iso) || !$billing_country_iso) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $billing_country_iso must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[a-zA-Z]{2}$/', $billing_country_iso)) {
            return [
                'result' => false,
                'message' => 'Invalid argument format given, $billing_country_iso not a valid iso code',
            ];
        }

        if ($shipping_country_iso != $billing_country_iso) {
            return [
                'result' => false,
                'message' => 'The given iso code do not match together',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if oney feature is available with given product quantity, address and amount
     *
     * @param bool $product_quantity
     * @param bool $address
     * @param bool $amount
     *
     * @return array
     */
    public function isOneyElligible($product_quantity = false, $address = false, $amount = false)
    {
        if (!is_bool($product_quantity)) {
            return [
                'result' => false,
                'code' => 'product_quantity',
                'message' => 'Invalid argument given, $product_quantity must be a boolean',
            ];
        }

        if (!is_bool($address)) {
            return [
                'result' => false,
                'code' => 'address',
                'message' => 'Invalid argument given, $address must be a boolean',
            ];
        }

        if (!is_bool($amount)) {
            return [
                'result' => false,
                'code' => 'amount',
                'message' => 'Invalid argument given, $amount must be a boolean',
            ];
        }

        if (!$product_quantity) {
            return [
                'result' => false,
                'code' => 'product_quantity',
                'message' => 'Oney is not avaible. Reason: An error is return with the products quantity',
            ];
        }

        if (!$address) {
            return [
                'result' => false,
                'code' => 'address',
                'message' => 'Oney is not avaible. Reason: An error is return with the address',
            ];
        }

        if (!$amount) {
            return [
                'result' => false,
                'code' => 'amount',
                'message' => 'Oney is not avaible. Reason: An error is return with the amount',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given email is available for oney usage
     *
     * @param string $email
     *
     * @return array
     */
    public function isOneyEmail($email = '')
    {
        if (!is_string($email) || !$email) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $email must be a non empty string',
            ];
        }

        if (strlen($email) > 100 && strpos($email, '+')) {
            return [
                'result' => false,
                'code' => 'length-char',
                'message' => 'Invalid email lenght given, Oney email is limited to 100 char and "+" usage is forbidden',
            ];
        }

        if (strpos($email, '+')) {
            return [
                'result' => false,
                'code' => 'char',
                'message' => 'Invalid character found in given email, "+" usage is forbidden',
            ];
        }

        if (strlen($email) > 100) {
            return [
                'result' => false,
                'code' => 'length',
                'message' => 'Invalid email lenght given, Oney email is limited to 100 char',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given payment is paid
     *
     * @param null $payment
     *
     * @return array
     */
    public function isPaid($payment = null)
    {
        if (!is_object($payment) || !$payment) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $payment must be a non empty object',
            ];
        }

        if (!isset($payment->is_paid)) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment does not contain is_paid props',
            ];
        }

        if (!$payment->is_paid) {
            return [
                'result' => false,
                'message' => 'Payment is not paid',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given object is valid payment
     *
     * @param object $payment
     *
     * @return array
     */
    public function isPayment($payment = null)
    {
        if (!is_object($payment) || !$payment) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $payment must be a non empty object',
            ];
        }

        if (!isset($payment->id) || !$payment->id) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment does not valid id',
            ];
        }

        if ((!isset($payment->amount) || !$payment->amount)
            && (!isset($payment->authorized_amount) || !$payment->authorized_amount)) {
            return [
                'result' => false,
                'message' => 'Missing props, $payment does not valid amount nor authorized_amount',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given phone number is valid format (E.164)
     *
     * @param string $phone
     *
     * @return array
     */
    public function isPhoneNumber($phone = '')
    {
        if (!is_string($phone) || !$phone) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $phone must be a non empty string',
            ];
        }

        if (!preg_match('/^[+0-9. ()\/-]{6,}$/', $phone) || (int) preg_match_all('/[0-9]/', $phone) > 15) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $phone must be a valid phone number format (E.164)',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given phone number is valid mobile phone number.
     *
     * @param $iso_code
     * @param false $phone_number
     *
     * @return array
     */
    public function isValidMobilePhoneNumber($phone_number = '', $iso_code = '')
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $phone_number must be a valid phone number',
            ];
        }

        if (!is_string($iso_code) || !$iso_code) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $iso_code must be a non empty string',
            ];
        }

        try {
            $phone_util = libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if ($phone_util->getRegionCodeForCountryCode($parsed->getCountryCode()) != $iso_code) {
                return [
                    'result' => false,
                    'message' => '$iso_code is wrong',
                ];
            }

            $is_mobile = $phone_util->getNumberType($parsed);

            return [
                    'result' => in_array($is_mobile, [1, 2], true),
                    'message' => '',
                ];
        } catch (libphonenumberlight\NumberParseException $e) {
            // todo : Add error Log
            return [
                'result' => false,
                'message' => 'Error, the mobile phone number is not valid',
            ];
        }
    }

    /**
     * @description Check if the given amount to refund is valid
     *
     * @param int $amount
     * @param int $limit
     *
     * @return array
     */
    public function isRefundableAmount($amount = 0, $limit = 0)
    {
        if (!is_int($amount) || !$amount) {
            return [
                'result' => false,
                'code' => 'format',
                'message' => 'Invalid argument given, $amount must be a non null integer',
            ];
        }

        if (!is_int($limit) || !$limit) {
            return [
                'result' => false,
                'code' => 'format',
                'message' => 'Invalid argument given, $limit must be a non null integer',
            ];
        }

        if ($amount < 10) {
            return [
                'result' => false,
                'code' => 'lower',
                'message' => 'The given amount is to low',
            ];
        }

        if ($amount > $limit) {
            return [
                'result' => false,
                'code' => 'upper',
                'message' => 'The given amount exceed the given limit',
            ];
        }

        return [
            'result' => true,
            'message' => '',
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
        $limits_date = date('Y-m-d H:i:s', strtotime('-10 minutes'));

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
     * @description Check is given product quantity is valid
     *
     * @param int $quantity
     * @param int $limit
     *
     * @return array
     */
    public function isValidProductQuantity($quantity = 0, $limit = 0)
    {
        if (!is_int($quantity) || !$quantity) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $quantity must be a non null integer',
            ];
        }

        if (!is_int($limit) || !$limit) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $limit must be a non null integer',
            ];
        }

        if ($quantity > $limit) {
            return [
                'result' => false,
                'message' => 'The given quantity given exceed the limit',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }
}
