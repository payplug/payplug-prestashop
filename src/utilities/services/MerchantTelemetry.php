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

if (!defined('_PS_VERSION_')) {
    exit;
}

use Payplug\PluginTelemetry;

class MerchantTelemetry
{
    /**
     * @description Send data to mpdc
     *
     * @param string $datas
     *
     * @return array
     */
    public function send($datas = '')
    {
        if (!is_string($datas) || !$datas) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Invalid argument given, $datas must be a non empty string',
            ];
        }

        try {
            $send = PluginTelemetry::Send($datas);
            $response = [
                'result' => 201 == (int) $send['httpStatus'] ? true : false,
                'code' => $send['httpStatus'],
                'message' => '',
            ];
        } catch (\Exception $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }
}
