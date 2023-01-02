<?php
/**
 * 2013 - 2023 PayPlug SAS
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
 * @copyright 2013 - 2023 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\utilities\validators;

class accountValidator
{
    /**
     * @description Compare getAccount API key with API key stored in database to check if API key is valide
     *
     * @param string $api_key_account
     * @param string $api_key_database
     *
     * @return array
     */
    public function isApiKeyInvalidated($api_key_account = '', $api_key_database = '')
    {
        if (!is_string($api_key_account) || !$api_key_account) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $api_key_account must be a non empty string',
            ];
        }

        if (!is_string($api_key_database) || !$api_key_database) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $api_key_database must be a non empty string',
            ];
        }

        if ($api_key_account != $api_key_database) {
            return [
                'result' => false,
                'message' => 'The $api_key_account is different from the $api_key_database. Your API key is invalidated',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given API key is valid
     *
     * @param string $api_key
     *
     * @return array
     */
    public function isApiKey($api_key = '')
    {
        if (!is_string($api_key) || !$api_key) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $api_key must be a non empty string',
            ];
        }

        if (!(strpos($api_key, 'sk_live_') === 0
            || strpos($api_key, 'pk_live_') === 0
            || strpos($api_key, 'sk_test_') === 0
            || strpos($api_key, 'pk_test_') === 0)
            || strlen($api_key) != 30) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $api_key is not allowed',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Determine if the account has a live api key
     *
     * @param mixed $api_key
     *
     * @return array
     */
    public function hasLiveKey($api_key = '')
    {
        if (!is_string($api_key) || !$api_key) {
            return [
                'result' => false,
                'message' => 'The account does not have LIVE API key',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }
}
