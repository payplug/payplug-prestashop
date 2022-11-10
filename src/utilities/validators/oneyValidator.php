<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\utilities\validators;

class oneyValidator
{
    /**
     * @description  check if the oney is activated
     * for belgium/spain
     *
     * @param $isOneyCountryValidFeature
     * @param $oneyAllowedCountries
     * @param $country
     */
    public function isOneyAllowedCountry($oneyAllowedCountries = '', $country = '')
    {
        if (!is_string($oneyAllowedCountries) || !$oneyAllowedCountries) {
            return [
                'result' => false,
                'message' => 'Invalid oney allowed countries format',
            ];
        }
        if (!is_string($country) || !$country) {
            return [
                'result' => false,
                'message' => 'Invalid country format',
            ];
        }

        return [
            'result' => in_array($country, explode(',', $oneyAllowedCountries)),
            'message' => 'Success',
        ];
    }
}
