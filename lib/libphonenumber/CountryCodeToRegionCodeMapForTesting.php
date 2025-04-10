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

class CountryCodeToRegionCodeMapForTesting
{
    // A mapping from a country code to the region codes which denote the
    // country/region represented by that country code. In the case of multiple
    // countries sharing a calling code, such as the NANPA countries, the one
    // indicated with "isMainCountryForCode" in the metadata should be first.

    public static $countryCodeToRegionCodeMapForTesting = [
        1 => [
            0 => 'US',
            1 => 'BB',
            2 => 'BS',
            3 => 'CA',
        ],
        7 => [
            0 => 'RU',
        ],
        33 => [
            0 => 'FR',
        ],
        36 => [
            0 => 'HU',
        ],
        39 => [
            0 => 'IT',
        ],
        44 => [
            0 => 'GB',
            1 => 'GG',
        ],
        46 => [
            0 => 'SE',
        ],
        48 => [
            0 => 'PL',
        ],
        49 => [
            0 => 'DE',
        ],
        52 => [
            0 => 'MX',
        ],
        54 => [
            0 => 'AR',
        ],
        55 => [
            0 => 'BR',
        ],
        61 => [
            0 => 'AU',
            1 => 'CC',
            2 => 'CX',
        ],
        64 => [
            0 => 'NZ',
        ],
        65 => [
            0 => 'SG',
        ],
        81 => [
            0 => 'JP',
        ],
        82 => [
            0 => 'KR',
        ],
        86 => [
            0 => 'CN',
        ],
        244 => [
            0 => 'AO',
        ],
        262 => [
            0 => 'RE',
            1 => 'YT',
        ],
        290 => [
            0 => 'TA',
        ],
        374 => [
            0 => 'AM',
        ],
        375 => [
            0 => 'BY',
        ],
        376 => [
            0 => 'AD',
        ],
        800 => [
            0 => '001',
        ],
        882 => [
            0 => '001',
        ],
        971 => [
            0 => 'AE',
        ],
        979 => [
            0 => '001',
        ],
        998 => [
            0 => 'UZ',
        ],
    ];
}
