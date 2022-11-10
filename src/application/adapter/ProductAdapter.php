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

use PayPlug\src\interfaces\ProductInterface;
use Product;

class ProductAdapter implements ProductInterface
{
    public function product($method)
    {
        if (isset($method)) {
            return Product::$method;
        }
    }

    /**
     * @deprecated since 1.6.1.X Use getIdProductAttributeByIdAttributes() instead
     *
     * @param mixed $idProduct
     * @param mixed $group
     */
    public function getIdProductAttributesByIdAttributes($idProduct, $group)
    {
        return Product::getIdProductAttributesByIdAttributes($idProduct, $group);
    }

    public function getIdProductAttributeByIdAttributes($idProduct, $group)
    {
        return Product::getIdProductAttributeByIdAttributes($idProduct, $group);
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
        return Product::getPriceStatic(
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
}
