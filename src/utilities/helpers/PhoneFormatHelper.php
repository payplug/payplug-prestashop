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

class PhoneFormatHelper
{
    /**
     * Territories using the French numbering habits, where shoppers often type a
     * metropolitan number in national format.
     */
    const FRENCH_NUMBERING_TERRITORIES = ['GP', 'MQ', 'RE', 'GF', 'YT', 'PM', 'BL', 'MF', 'NC', 'PF', 'WF'];

    /**
     * @description Format a phone number to E.164 for the Payplug API.
     *              Attempts, in order: the address country; FR for a French
     *              numbering territory; then, when not strict, any number
     *              libphonenumber considers valid.
     *
     * @param string $phone
     * @param string $iso
     * @param bool $strict
     *
     * @throws \PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException
     *
     * @return string
     */
    public static function toE164($phone, $iso, $strict = false)
    {
        $phone = (string) $phone;
        $iso = strtoupper((string) $iso);

        try {
            return \PayplugUnifiedCore\Utilities\Helpers\PhoneHelper::toE164($phone, $iso);
        } catch (\PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException $e) {
            $region_exception = $e;
        }

        if (in_array($iso, self::FRENCH_NUMBERING_TERRITORIES, true)) {
            try {
                return \PayplugUnifiedCore\Utilities\Helpers\PhoneHelper::toE164($phone, 'FR');
            } catch (\PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException $e) {
            }
        }

        if ($strict) {
            throw $region_exception;
        }

        $phone_util = \libphonenumber\PhoneNumberUtil::getInstance();

        try {
            $number = $phone_util->parse($phone, $iso);
        } catch (\libphonenumber\NumberParseException $e) {
            throw new \PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException($e->getMessage(), 0, $e);
        }

        if (!$phone_util->isValidNumber($number)) {
            throw $region_exception;
        }

        return $phone_util->format($number, \libphonenumber\PhoneNumberFormat::E164);
    }

    /**
     * @description Check that a phone number is a mobile line accepted by toE164()
     *              for the given country and mode.
     *
     * @param string $phone
     * @param string $iso
     * @param bool $strict
     *
     * @return bool
     */
    public static function isMobile($phone, $iso, $strict = true)
    {
        if (!is_string($phone) || '' === $phone || !is_string($iso) || '' === $iso) {
            return false;
        }

        try {
            $e164 = self::toE164($phone, $iso, $strict);
        } catch (\PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException $e) {
            return false;
        }

        $phone_util = \libphonenumber\PhoneNumberUtil::getInstance();

        try {
            $type = $phone_util->getNumberType($phone_util->parse($e164, null));
        } catch (\libphonenumber\NumberParseException $e) {
            return false;
        }

        return \libphonenumber\PhoneNumberType::MOBILE === $type
            || \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE === $type;
    }
}
