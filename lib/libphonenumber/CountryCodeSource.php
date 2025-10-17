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

namespace PayPlug\lib\libphonenumber;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Country code source from number.
 */
class CountryCodeSource
{
    /**
     * The country_code is derived based on a phone number with a leading "+", e.g. the French
     * number "+33 1 42 68 53 00".
     */
    const FROM_NUMBER_WITH_PLUS_SIGN = 0;
    /**
     * The country_code is derived based on a phone number with a leading IDD, e.g. the French
     * number "011 33 1 42 68 53 00", as it is dialled from US.
     */
    const FROM_NUMBER_WITH_IDD = 1;
    /**
     * The country_code is derived based on a phone number without a leading "+", e.g. the French
     * number "33 1 42 68 53 00" when defaultCountry is supplied as France.
     */
    const FROM_NUMBER_WITHOUT_PLUS_SIGN = 2;
    /**
     * The country_code is derived NOT based on the phone number itself, but from the defaultCountry
     * parameter provided in the parsing function by the clients. This happens mostly for numbers
     * written in the national format (without country code). For example, this would be set when
     * parsing the French number "01 42 68 53 00", when defaultCountry is supplied as France.
     */
    const FROM_DEFAULT_COUNTRY = 3;

    const UNSPECIFIED = 4;
}
