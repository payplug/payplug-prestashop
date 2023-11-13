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

class UserHelper
{
    /**
     * @description check if user is logged or not
     *
     * @param $isEmail
     * @param $isApiKey
     *
     * @return array
     */
    public function isLogged($isEmail, $isApiKey)
    {
        if (!is_bool($isEmail)) {
            return [
                'result' => false,
                'message' => '$isEmail must be a bool type',
            ];
        }
        if (!is_bool($isApiKey)) {
            return [
                'result' => false,
                'message' => '$isApiKey must be a bool type',
            ];
        }

        if (!$isEmail) {
            return [
                'result' => false,
                'message' => 'user is not logged because $email is not valid',
            ];
        }
        if (!$isApiKey) {
            return [
                'result' => false,
                'message' => 'user is not logged because $isApiKey is not valid',
            ];
        }

        return [
            'result' => $isEmail && $isApiKey,
            'message' => '$user is logged',
        ];
    }
}
