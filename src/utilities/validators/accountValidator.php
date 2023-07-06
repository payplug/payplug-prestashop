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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
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

        if (!(bool) preg_match('/^[a-zA-Z0-9_]*$/', $api_key)) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $api_key contained invalid characters',
            ];
        }

        if ((0 !== strpos($api_key, 'sk_live_')
            && 0 !== strpos($api_key, 'pk_live_')
            && 0 !== strpos($api_key, 'sk_test_')
            && 0 !== strpos($api_key, 'pk_test_'))) {
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

    /**
     * @description Check if a given email is valid
     *
     * @param $email
     *
     * @return array
     */
    public function isEmail($email)
    {
        if (!is_string($email) || !$email) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $email must be a non empty string',
            ];
        }
        if (!(bool) preg_match('/^\b[\w\.\+-]+@[\w\.-]+\.\w{2,}\b$/', $email)) {
            return [
                'result' => false,
                'message' => 'Invalid email format given, $email given is not valid',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if a given password is valid
     *
     * @param $password
     *
     * @return array
     */
    public function isPassword($password)
    {
        if (!is_string($password) || !$password) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $password must be a non empty string',
            ];
        }

        if (strlen($password) < 5) {
            return [
                'result' => false,
                'message' => 'Invalid $password given, it is to short',
            ];
        }

        if (strlen($password) > 72) {
            return [
                'result' => false,
                'message' => 'Invalid $password given, it is to long',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }
}
