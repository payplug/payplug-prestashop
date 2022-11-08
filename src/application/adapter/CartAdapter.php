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

use Cart;
use PayPlug\src\interfaces\CartInterface;

class CartAdapter implements CartInterface
{
    public static function factory()
    {
        return new self();
    }

    public function get($id_cart = false)
    {
        if (!is_int($id_cart)) {
            $id_cart = false;
        }

        return new Cart($id_cart);
    }

    public function isVirtualCart($cart)
    {
        if (!is_object($cart)) {
            return false;
        }

        return $cart->isVirtualCart();
    }

    public function getProducts($cart)
    {
        if (!is_object($cart)) {
            return false;
        }

        return $cart->getProducts();
    }

    public function nbProducts($cart)
    {
        if (!is_object($cart)) {
            return false;
        }

        return $cart->nbProducts();
    }

    public function isGuestCartByCartId($idCart = false)
    {
        if (!is_int($idCart)) {
            return false;
        }

        return Cart::isGuestCartByCartId($idCart);
    }
}
