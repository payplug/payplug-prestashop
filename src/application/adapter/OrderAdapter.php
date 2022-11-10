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

namespace PayPlug\src\application\adapter;

use Order;
use PayPlug\src\interfaces\OrderInterface;

class OrderAdapter implements OrderInterface
{
    public function get($idOrder = null)
    {
        if (!is_int($idOrder)) {
            $idOrder = false;
        }

        return new Order($idOrder);
    }

    /**
     * @deprecated since 1.7.1.0 Use getIdByCartId() instead
     *
     * @param null|mixed $idCart
     */
    public function getOrderByCartId($idCart = null)
    {
        return Order::getOrderByCartId($idCart);
    }
}
