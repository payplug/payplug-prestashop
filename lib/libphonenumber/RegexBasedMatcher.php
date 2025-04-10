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
 * Class RegexBasedMatcher.
 *
 * @internal
 */
class RegexBasedMatcher implements MatcherAPIInterface
{
    public static function create()
    {
        return new static();
    }

    /**
     * Returns whether the given national number (a string containing only decimal digits) matches
     * the national number pattern defined in the given {@code PhoneNumberDesc} message.
     *
     * @param string $number
     * @param bool $allowPrefixMatch
     *
     * @return bool
     */
    public function matchNationalNumber($number, PhoneNumberDesc $numberDesc, $allowPrefixMatch)
    {
        $nationalNumberPattern = $numberDesc->getNationalNumberPattern();

        // We don't want to consider it a prefix match when matching non-empty input against an empty
        // pattern

        if (0 === strlen($nationalNumberPattern)) {
            return false;
        }

        return $this->match($number, $nationalNumberPattern, $allowPrefixMatch);
    }

    /**
     * @param string $number
     * @param string $pattern
     * @param $allowPrefixMatch
     *
     * @return bool
     */
    private function match($number, $pattern, $allowPrefixMatch)
    {
        $matcher = new Matcher($pattern, $number);

        if (!$matcher->lookingAt()) {
            return false;
        }

        return $matcher->matches() ? true : $allowPrefixMatch;
    }
}
