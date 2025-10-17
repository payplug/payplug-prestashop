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

class cardValidator
{
    /**
     * @description Check the expiration for given year and month
     *
     * @param string $month
     * @param string $year
     *
     * @return array
     */
    public function isValidExpiration($month = '', $year = '')
    {
        if (!is_string($month)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $month must be a string',
            ];
        }
        if (!(bool) preg_match('/^[0-9]{1,2}$/', $month)) {
            return [
                'result' => false,
                'message' => 'Invalid argument format for $month given',
            ];
        }

        if (!is_string($year)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $year must be a string',
            ];
        }
        if (!(bool) preg_match('/^[0-9]{4}$/', $year)) {
            return [
                'result' => false,
                'message' => 'Invalid argument format for $year given',
            ];
        }

        $date = strtotime($year . '-' . $month . '-01');
        $limit_date = date('Y-m-d', strtotime('+1 month', $date));

        if ('1970' == date('Y', strtotime($limit_date))) {
            return [
                'result' => false,
                'message' => 'Invalid date given through $month and/or $year',
            ];
        }

        if ($limit_date <= date('Y-m-d')) {
            return [
                'result' => false,
                'message' => 'This card is expired',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }
}
