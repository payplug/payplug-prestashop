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
            return \Product::getIdProductAttributesByIdAttributes($idProduct, $group);
        }

        return \Product::getIdProductAttributeByIdAttributes($idProduct, $group);
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
}
