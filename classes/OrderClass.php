<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

use Db;
use OrderState;

class OrderClass extends \PaymentModule
{
    /**
     * @description Add Order Payment
     *
     * @param int $id_order
     * @param string $id_payment
     * @return bool
     */
    public function addPayplugOrderPayment($id_order, $id_payment)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'payplug_order_payment (id_order, id_payment) 
                VALUE (' . (int)$id_order . ',\'' . pSQL($id_payment) . '\')';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param null $id_lang
     * @return array
     */
    public function getOrderStates($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = $this->context->language->id;
        }
        return OrderState::getOrderStates($id_lang);
    }

    /**
     * @description
     * get order payment
     *
     * @param int $id_order
     * @return integer
     */
    public function getPayplugOrderPayment($id_order)
    {
        $sql = 'SELECT id_payment 
                FROM ' . _DB_PREFIX_ . 'payplug_order_payment   
                WHERE id_order = ' . (int)$id_order;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @description
     * get all order payment for given id order
     *
     * @param int $id_order
     * @return array
     */
    public function getPayplugOrderPayments($id_order)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'payplug_order_payment 
                WHERE id_order = ' . (int)$id_order;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @description Get the current Order State Id for a given Order ID
     *
     * @param bool $id_order
     * @return integer|false
     */
    public function getCurrentOrderState($id_order = false)
    {
        if (!$id_order) {
            return false;
        }

        $sql = 'SELECT `current_state` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = ' . (int)$id_order;
        return Db::getInstance()->getValue($sql);
    }
}
