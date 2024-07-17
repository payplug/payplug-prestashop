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

namespace PayPlug\src\models\repositories;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderRepository extends EntityRepository
{
    public function __construct($prefix = '', $dependencies = null)
    {
        parent::__construct($prefix, $dependencies);
        $this->table_name = $this->prefix . 'orders';
    }

    /**
     * @description Get all domains use by the merchant
     *
     * @return string
     */
    public function getCurrentOrders()
    {
        $current_date = date('Y-m-d');
        $result = $this
            ->select()
            ->fields('`id_order`')
            ->from($this->table_name)
            ->where('`module` = "payplug"')
            ->where('`date_add` > "' . $this->escape($current_date) . '"')
            ->build();

        return $result ?: [];
    }

    /**
     * @description Get all order for a given cart id
     *
     * @param int $cart_id
     *
     * @return array
     */
    public function getByIdCart($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . 'orders')
            ->where('id_cart = ' . (int) $cart_id)
            ->build();

        return $result ?: [];
    }

    /**
     * @description Get all order for a given module name
     *
     * @param string $name
     *
     * @return array
     */
    public function getByModule($name = '')
    {
        if (!is_string($name) || !$name) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . 'orders')
            ->where('module = \'' . $this->escape($name) . '\'')
            ->build()
        ;

        return $result ?: [];
    }

    /**
     * @description Get the current order state id for a given order id
     *
     * @param int $order_id
     *
     * @return int
     */
    public function getCurrentOrderState($order_id = false)
    {
        if (!is_int($order_id) || !$order_id) {
            return 0;
        }

        $result = $this
            ->select()
            ->fields('current_state')
            ->from($this->prefix . 'orders')
            ->where('id_order = ' . (int) $order_id)
            ->build('unique_value');

        return $result ?: 0;
    }
}
