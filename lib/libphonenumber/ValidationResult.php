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
 * Possible outcomes when testing if a PhoneNumber is possible.
 */
class ValidationResult
{
    /**
     * The number length matches that of valid numbers for this region.
     */
    const IS_POSSIBLE = 0;

    /**
     * The number has an invalid country calling code.
     */
    const INVALID_COUNTRY_CODE = 1;

    /**
     * The number is shorter than all valid numbers for this region.
     */
    const TOO_SHORT = 2;

    /**
     * The number is longer than all valid numbers for this region.
     */
    const TOO_LONG = 3;

    /**
     * The number length matches that of local numbers for this region only (i.e. numbers that may
     * be able to be dialled within an area, but do not have all the information to be dialled from
     * anywhere inside or outside the country).
     */
    const IS_POSSIBLE_LOCAL_ONLY = 4;

    /**
     * The number is longer than the shortest valid numbers for this region, shorter than the
     * longest valid numbers for this region, and does not itself have a number length that matches
     * valid numbers for this region. This can also be returned in the case where
     * isPossibleNumberForTypeWithReason was called, and there are no numbers of this type at all
     * for this region.
     */
    const INVALID_LENGTH = 5;
}
