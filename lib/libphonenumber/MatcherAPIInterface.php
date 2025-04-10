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
 * Interface MatcherAPIInterface.
 *
 * Internal phonenumber matching API used to isolate the underlying implementation of the
 * matcher and allow different implementations to be swapped in easily.
 *
 * @internal
 */
interface MatcherAPIInterface
{
    /**
     * Returns whether the given national number (a string containing only decimal digits) matches
     * the national number pattern defined in the given {@code PhoneNumberDesc} message.
     *
     * @param string $number
     * @param bool $allowPrefixMatch
     *
     * @return bool
     */
    public function matchNationalNumber($number, PhoneNumberDesc $numberDesc, $allowPrefixMatch);
}
