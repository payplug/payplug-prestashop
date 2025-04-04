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

class PhoneHelper
{
    public static function isMobilePhoneNumber($iso_code = '', $phone_number = false)
    {
        if (!is_string($iso_code) || $iso_code) {
            return false;
        }

        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return false;
        }

        try {
            $phone_util = libphonenumber\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if ($phone_util->getRegionCodeForCountryCode($parsed->getCountryCode()) != $iso_code) {
                return false;
            }

            $is_mobile = $phone_util->getNumberType($parsed);

            return (bool) in_array($is_mobile, [1, 2], true);
        } catch (libphonenumber\NumberParseException $e) {
            // @todo : Add Log
            return false;
        }
    }
}
