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

namespace PayPlug\src\interfaces;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CartInterface
{
    public function get($idCart);

    public function isVirtualCart($cart);

    public function getProducts($cart);

    public function nbProducts($cart);

    public function isGuestCartByCartId($idCart);

    public function getOrderTotal($id_cart, $with_tax, $id_carrier);

    public function getOrderTotalWithoutShipping($id_cart, $with_tax);

    public function getOrderTotalDiscount($id_cart, $with_tax, $id_carrier);

    public function getDeliveryOptionList($id_cart);

    public function isCarrierInRange($id_carrier, $id_zone);
}
