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

namespace PayPlug\backward;

use Tools;
use ValidateCore as Validate;
use ShopCore as Shop;
use DbCore as Db;
use OrderCore as Order;

class PayPlugBackward
{
    const PASSWORD_LENGTH = 5;

    /**
     * Check if plaintext password is valid
     * Size is limited by `password_hash()` (72 chars).
     *
     * @param string $plaintextPasswd Password to validate
     * @param int $size
     *
     * @return bool Indicates whether the given string is a valid plaintext password
     *
     * @since 1.7.0
     */
    public static function isPlaintextPassword($plaintextPasswd, $size = self::PASSWORD_LENGTH)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            // The password lenght is limited by `password_hash()`
            return Tools::strlen($plaintextPasswd) >= $size && Tools::strlen($plaintextPasswd) <= 72;
        } else {
            return Validate::isPlaintextPassword($plaintextPasswd);
        }
    }

    /**
     * Get an order object by its cart id.
     *
     * @param int $id_cart Cart id
     *
     * @return OrderCore
     */
    public static function getByCartId($id_cart)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $sql = 'SELECT `id_order` 
            FROM `' . _DB_PREFIX_ . 'orders`
            WHERE `id_cart` = ' . (int) $id_cart .
                Shop::addSqlRestriction();

            $result = Db::getInstance()->getValue($sql);

            return !empty($result) ? (int) $result : false;
        } else {
            return Order::getByCartId($id_cart);
        }
    }
}
