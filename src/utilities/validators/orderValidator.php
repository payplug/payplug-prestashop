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

class orderValidator
{
    /**
     * @description Check if given order match with the given cart id.
     *
     * @param object $order
     * @param int $id_cart
     *
     * @return array
     */
    public function isCreated($order = null, $id_cart = 0)
    {
        if (!is_object($order) || !$order) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $order must be a non empty object',
            ];
        }
        if (!is_int($id_cart) || !$id_cart) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $id_cart must be a non null integer',
            ];
        }
        if (!isset($order->id) || !$order->id) {
            return [
                'result' => false,
                'message' => 'Invalid object given, $order should have a non null id',
            ];
        }
        if (!isset($order->id_cart) || !$order->id_cart) {
            return [
                'result' => false,
                'message' => 'Invalid object given, $order should have a non null cart id',
            ];
        }

        if ($order->id_cart != $id_cart) {
            return [
                'result' => false,
                'message' => 'Given order does not match with given cart id',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if two given amount are the same
     *
     * @param float $first_amount
     * @param float $second_amount
     *
     * @return array
     */
    public function isSameAmount($first_amount = 0, $second_amount = 0)
    {
        if (!is_float($first_amount) || !$first_amount) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $first_amount must be a non null float',
            ];
        }

        if (!is_float($second_amount) || !$second_amount) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $second_amount must be a non null float',
            ];
        }

        if ($first_amount != $second_amount) {
            return [
                'result' => false,
                'message' => 'The given amounts are differents',
            ];
        }

        return [
            'result' => true,
            'message' => 'The given amounts are the same',
        ];
    }
}
