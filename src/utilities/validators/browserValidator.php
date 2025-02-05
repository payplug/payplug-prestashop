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

namespace PayPlug\src\utilities\validators;

if (!defined('_PS_VERSION_')) {
    exit;
}

class browserValidator
{
    /**
     * @description check if the device is ApplePay compatible
     *
     * @param string $browser
     *
     * @return array
     */
    public function isApplePayCompatible($browser = '')
    {
        if (!is_string($browser) || !$browser) {
            return [
                'result' => false,
                'message' => 'Invalid parameter given, $browser must be a non empty string.',
            ];
        }
        if ('Safari' != $browser) {
            return [
                'result' => false,
                'message' => 'This browser is not applepay compatible.',
            ];
        }

        return [
            'result' => true,
            'message' => 'This browser is applepay compatible.',
        ];
    }
}
