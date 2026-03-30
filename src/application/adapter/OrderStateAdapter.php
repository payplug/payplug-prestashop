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

namespace PayPlug\src\application\adapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\interfaces\OrderStateInterface;

class OrderStateAdapter implements OrderStateInterface
{
    private $orderState;

    public function __construct($id = null)
    {
        $this->orderState = new \OrderState($id);
    }

    public function delete()
    {
        return $this->orderState->delete();
    }

    public function get($id = null, $idLang = null)
    {
        return new \OrderState($id, $idLang);
    }

    public static function getOrderState($id = null)
    {
        return new \OrderState($id);
    }

    public static function getOrderStates($id_lang = null)
    {
        return \OrderState::getOrderStates($id_lang);
    }

    public function softDelete($id = 0)
    {
        if (!is_int($id) || !$id) {
            return false;
        }

        $orderState = $this->get($id);
        if (method_exists($orderState, 'softDelete')) {
            return $orderState->softDelete();
        }

        return $orderState->delete();
    }
}
