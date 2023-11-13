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
    public function send($api_key = '', $datas = '')
    {
        if (!is_string($api_key) || !$api_key) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Invalid argument given, $api_key must be a non empty string',
            ];
        }

        if (!is_string($datas) || !$datas) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Invalid argument given, $datas must be a non empty string',
            ];
        }

        try {
            $send = PluginTelemetry::mockSend($datas);
            $response = [
                'code' => $send['httpStatus'],
                'result' => 201 == (int) $send['httpStatus'] ? true : false,
                'message' => '',
            ];
        } catch (\Exception $e) {
            $response = [
                'code' => (int) $e->getCode(),
                'result' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }
}
