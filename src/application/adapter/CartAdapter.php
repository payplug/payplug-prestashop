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
     * @description  update product quantity
     * related to a given cart
     * to cart
     *
     * @param $cart
     * @param mixed $id_cart
     * @param mixed $quantity
     * @param mixed $id_product
     * @param mixed $id_lang
     * @param mixed $id_currency
     *
     * @return mixed
     */
    public function updateQty($id_cart = 0, $quantity = 0, $id_product = 0, $id_lang = 0, $id_currency = 0)
    {
        if (!is_int($id_cart)) {
            return [];
        }
        if (!is_int($quantity)) {
            return [];
        }
        if (!is_int($id_product)) {
            return [];
        }
        $cart = new \Cart($id_cart);

        return $cart->updateQty($quantity, $id_product);
    }

    /**
     * @description  update the address id in the cart
     *
     * @param int $id_cart
     * @param int $current_address_delivery
     * @param int $id_address_delivery
     *
     * @return array
     */
    public function updateAddressId($id_cart = 0, $current_address_delivery = 0, $id_address_delivery = 0)
    {
        if (!is_int($id_cart)) {
            return [];
        }
        if (!is_int($current_address_delivery)) {
            return [];
        }

        if (!is_int($id_address_delivery)) {
            return [];
        }
        $cart = new \Cart((int) $id_cart);

        return $cart->updateAddressId($current_address_delivery, $id_address_delivery);
    }

    /**
     * @description Creates a new cart for the given context
     *
     * @param $context
     * @param int $id_customer_address
     *
     * @return \Cart|false
     */
    public function createNewCart($context, $id_customer_address = 0)
    {
        if (!is_object($context)) {
            return false;
        }

        if (!is_int($id_customer_address)) {
            return false;
        }
        $cart = new \Cart();
        $cart->id_lang = (int) $context->cookie->id_lang;
        $cart->id_currency = (int) $context->cookie->id_currency;
        $cart->id_guest = (int) $context->cookie->id_guest;
        $cart->id_shop_group = (int) $context->shop->id_shop_group;
        $cart->id_shop = (int) $context->shop->id;

        if ($context->cookie->id_customer) {
            $cart->id_customer = (int) $context->cookie->id_customer;
            $cart->id_address_delivery = $id_customer_address;
            $cart->id_address_invoice = $id_customer_address;
        } else {
            $cart->id_address_delivery = 0;
            $cart->id_address_invoice = 0;
        }

        $cart->add();

        return $cart;
    }
}
