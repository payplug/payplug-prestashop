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

use PayPlug\src\interfaces\CartInterface;

class CartAdapter implements CartInterface
{
    private $cart;

    public function __construct()
    {
        $this->cart = new \Cart();
    }

    public function get($id_cart = false)
    {
        if (!is_int($id_cart)) {
            $id_cart = false;
        }

        return new \Cart($id_cart);
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

        $cart = $this->cart;

        return $cart::isGuestCartByCartId($idCart);
    }

    public function getOrderTotal($id_cart = 0, $with_tax = true, $id_carrier = null)
    {
        if (!is_int($id_cart) || !$id_cart) {
            return 0;
        }

        if (!is_bool($with_tax)) {
            return 0;
        }

        if (!is_int($id_carrier)) {
            return 0;
        }
        $cart = new \Cart((int) $id_cart);

        return $cart->getOrderTotal($with_tax, \Cart::BOTH, null, $id_carrier);
    }

    public function getOrderTotalWithoutShipping($id_cart = 0, $with_tax = true)
    {
        if (!is_int($id_cart) || !$id_cart) {
            return 0;
        }

        if (!is_bool($with_tax)) {
            return 0;
        }

        $cart = new \Cart((int) $id_cart);

        return $cart->getOrderTotal($with_tax, \Cart::BOTH_WITHOUT_SHIPPING);
    }

    public function getOrderTotalDiscount($id_cart = 0, $with_tax = true, $id_carrier = null)
    {
        if (!is_int($id_cart) || !$id_cart) {
            return 0;
        }

        if (!is_bool($with_tax)) {
            return 0;
        }

        $cart = new \Cart((int) $id_cart);

        return $cart->getOrderTotal($with_tax, \Cart::ONLY_DISCOUNTS, null, $id_carrier);
    }

    public function getDeliveryOptionList($id_cart = 0)
    {
        if (!is_int($id_cart)) {
            return [];
        }

        $cart = new \Cart((int) $id_cart);

        return $cart->getDeliveryOptionList();
    }

    /**
     * @description check if given cart
     * has already products
     *
     * @param $cart
     *
     * @return mixed
     */
    public function hasProducts($cart)
    {
        return $cart->hasProducts();
    }

    /**
     * @description  delete cart
     *
     * @param $cart
     *
     * @return mixed
     */
    public function delete($cart)
    {
        return $cart->delete();
    }

    /**
     * @description  update the cart
     *
     * @param $cart
     *
     * @return mixed
     */
    public function update($cart)
    {
        return $cart->update();
    }

    /**
     * @description  add products
     * to cart
     *
     * @param $cart
     *
     * @return mixed
     */
    public function updateQty($cart)
    {
        return $cart->updateQty();
    }
}
