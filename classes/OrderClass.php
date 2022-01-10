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

namespace PayPlug\classes;

use Db;
use DbQuery;
use OrderState;

class OrderClass
{
    private $constant;
    private $context;
    private $dependencies;
    private $query;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->query = $this->dependencies->getPlugin()->getQuery();
    }

    /**
     * @description Add Order Payment
     *
     * @param int $id_order
     * @param string $id_payment
     * @return bool
     */
    public function addPayplugOrderPayment($id_order, $id_payment)
    {
        $this->query
            ->insert()
            ->into($this->constant->get('_DB_PREFIX_') . 'payplug_order_payment')
            ->fields('id_order')    ->values((int)$id_order)
            ->fields('id_payment')  ->values($id_payment);

        return $this->query->build();
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

    public function getPayPlugOrderStates($module)
    {
        $this->query
            ->select()
            ->field('GROUP_CONCAT(id_order_state) as id_order_states')
            ->from($this->constant->get('_DB_PREFIX_') . 'order_state')
            ->where('module_name = "' . $module . '"');

        return $this->query->build('unique_value');
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
        $this->query
            ->select()
            ->field('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . 'payplug_order_payment')
            ->where('id_order = ' . (int)$id_order);

        return $this->query->build('unique_value');
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
        $this->query
            ->select()
            ->from($this->constant->get('_DB_PREFIX_') . 'payplug_order_payment')
            ->where('id_order = ' . (int)$id_order);

        return $this->query->build();
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

        $this->query
            ->select()
            ->field('current_state')
            ->from($this->constant->get('_DB_PREFIX_') . 'orders')
            ->where('id_order = ' . (int)$id_order);

        return $this->query->build('unique_value');
    }
}
