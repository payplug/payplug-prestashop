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

use PayPlug\src\interfaces\ProductInterface;

class ProductAdapter implements ProductInterface
{
    public function product($method)
    {
        if (isset($method)) {
            return \Product::$method;
        }
    }

    public function get($id_product = false)
    {
        if (!is_int($id_product)) {
            $id_product = false;
        }

        return new \Product($id_product);
    }

    /**
     * @description  get Id product by Attributes depending on the prestashop version
     *
     * @param mixed $idProduct
     * @param mixed $group
     */
    public function getIdProductAttributeByIdAttributes($idProduct, $group)
    {
        if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
            // @deprecated since 1.7.3.1 Use getIdProductAttributeByIdAttributes() instead
            return \Product::getIdProductAttributesByIdAttributes($idProduct, $group, true);
        }

        return \Product::getIdProductAttributeByIdAttributes($idProduct, $group, true);
    }

    public function getPriceStatic(
        $id_product,
        $usetax = true,
        $id_product_attribute = null,
        $decimals = 6,
        $divisor = null,
        $only_reduc = false,
        $usereduc = true,
        $quantity = 1
    ) {
        return \Product::getPriceStatic(
            $id_product,
            $usetax,
            $id_product_attribute,
            $decimals,
            $divisor,
            $only_reduc,
            $usereduc,
            $quantity
        );
    }

    public function hasAttributes($id_product)
    {
        $product = new \Product($id_product);

        return $product->hasAttributes();
    }

    /**
     * @description Check if a product (or combination) can be ordered regarding its stock,
     * its minimal quantity and the shop / product out of stock configuration
     *
     * @param int $id_product
     * @param int $id_product_attribute
     *
     * @return bool
     */
    public function isOrderableRegardingStock($id_product, $id_product_attribute = 0)
    {
        $minimal_quantity = $this->getMinimalQuantity($id_product, $id_product_attribute);

        // Pack::isInStock handles the out of stock configuration and the pack stock type (PS_PACK_STOCK_TYPE)
        if (\Pack::isPack((int) $id_product)) {
            return (bool) \Pack::isInStock((int) $id_product, $minimal_quantity);
        }

        if (\Product::isAvailableWhenOutOfStock(\StockAvailable::outOfStock((int) $id_product))) {
            return true;
        }

        $available_quantity = (int) \StockAvailable::getQuantityAvailableByProduct((int) $id_product, (int) $id_product_attribute);

        return $available_quantity >= $minimal_quantity;
    }

    /**
     * @description Get the default attribute (combination) of a product
     *
     * @param int $id_product
     *
     * @return int
     */
    public function getDefaultAttribute($id_product)
    {
        return (int) \Product::getDefaultAttribute((int) $id_product);
    }

    /**
     * @description Get the minimal quantity required to order a product (or combination)
     *
     * @param int $id_product
     * @param int $id_product_attribute
     *
     * @return int
     */
    private function getMinimalQuantity($id_product, $id_product_attribute = 0)
    {
        if (0 < (int) $id_product_attribute) {
            $combination = new \Combination((int) $id_product_attribute);

            return max(1, (int) $combination->minimal_quantity);
        }

        $product = new \Product((int) $id_product);

        return max(1, (int) $product->minimal_quantity);
    }
}
