<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\utilities\validators;

class lockValidator
{
    /**
     * @description Check if lock is epxired
     *
     * @param string $date
     *
     * @return array
     */
    public function isExpired($date = '')
    {
        if (!is_string($date) || !$date) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $date must be a non empty string',
            ];
        }

        if (!(bool) preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}\ [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $date)) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $date must be a date in format Y-m-d H:i:s',
            ];
        }

        $given_date = date('Y-m-d H:i:s', strtotime($date));
        $limits_date = date('Y-m-d H:i:s', strtotime('-2 minutes'));

        if ($given_date > $limits_date) {
            return [
                'result' => false,
                'message' => 'Lock is not expired',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }
}
