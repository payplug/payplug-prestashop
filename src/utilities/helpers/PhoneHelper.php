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
    /**
     * @deprecated in 5.1.0, use \PayplugUnifiedCore\Utilities\Helpers\PhoneHelper::isMobile() instead, to remove in 5.2.0
     *
     * @param string $iso_code
     * @param string $phone_number
     *
     * @return bool
     */
    public static function isMobilePhoneNumber($iso_code = '', $phone_number = false)
    {
        if (!is_string($iso_code) || !$iso_code) {
            return false;
        }

        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return false;
        }

        try {
            return \PayplugUnifiedCore\Utilities\Helpers\PhoneHelper::isMobile($phone_number, $iso_code);
        } catch (\PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException $e) {
            return false;
        }
    }
}
