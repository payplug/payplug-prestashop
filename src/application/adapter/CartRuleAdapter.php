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

use PayPlug\src\interfaces\CartRuleInterface;

class CartRuleAdapter implements CartRuleInterface
{
    private $cart_rule;

    public function __construct()
    {
        $this->cart_rule = new \CartRule();
    }

    /**
     * @description  get a CartRule object
     *
     * @param int $id_cart_rule
     *
     * @return \CartRule
     */
    public function get($id_cart_rule = false)
    {
        if (!is_int($id_cart_rule)) {
            $id_cart_rule = false;
        }

        return new \CartRule($id_cart_rule);
    }

    /**
     * @description add cart rules to the cart
     *
     * @param $context
     *
     * @return bool
     */
    public function autoAddToCart($context)
    {
        if (!is_object($context)) {
            return false;
        }
        $cart_rule = $this->cart_rule;

        return $cart_rule::autoAddToCart($context);
    }
}
