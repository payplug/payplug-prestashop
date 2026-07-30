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

namespace PayPlug\src\utilities\services;

use libphonenumber;
use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PhoneNumber
{
    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @deprecated in 5.1.0, use \PayplugUnifiedCore\Utilities\Helpers\PhoneHelper::toE164() instead, to remove in 5.2.0
     *
     * @description Return international formatted phone number (norm E.164).
     *
     * @param string $phone_number
     * @param string $iso_code
     *
     * @return string
     */
    public function formatPhoneNumber($phone_number = '', $iso_code = '')
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PhoneNumber::formatPhoneNumber - Invalid argument, given phone_number id must be a valid phone number.', 'error');

            return '';
        }
        if (empty($iso_code) || !is_string($iso_code)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PhoneNumber::formatPhoneNumber - Invalid argument, given iso_code must be non empty string.', 'error');

            return '';
        }

        // Assumed that iso_code should always be in uppercase
        $iso_code = strtoupper($iso_code);

        try {
            return \PayplugUnifiedCore\Utilities\Helpers\PhoneHelper::toE164($phone_number, $iso_code);
        } catch (\PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException $e) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PhoneNumber::formatPhoneNumber - Exception thrown: ' . $e->getMessage(), 'error');

            return '';
        }
    }

    protected function getLibInstance()
    {
        return libphonenumber\PhoneNumberUtil::getInstance();
    }
}
