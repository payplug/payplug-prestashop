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

use PayPlug\src\application\adapter\OrderStateAdapter;

class OrderClass
{
    private $constant;
    private $context;
    private $dependencies;
    private $query;
    private $orderState;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->orderState = $this->dependencies->getPlugin()->getOrderState();
    }

    /**
     * @description Add Order Payment
     *
     * @param int    $id_order
     * @param string $id_payment
     *
     * @return bool
     */
    public function addPayplugOrderPayment($id_order, $id_payment)
    {
        $this->query
            ->insert()
            ->into($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_order_payment')
            ->fields('id_order')->values((int) $id_order)
            ->fields('id_payment')->values($this->query->escape($id_payment))
        ;

        return $this->query->build();
    }

    /**
     * @param null $id_lang
     *
     * @return array
     */
    public function getOrderStates($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = $this->context->language->id;
        }

        return OrderStateAdapter::getOrderStates($id_lang);
    }

    public function getPayPlugOrderStates($module)
    {
        $this->query
            ->select()
            ->fields('GROUP_CONCAT(id_order_state) as id_order_states')
            ->from($this->constant->get('_DB_PREFIX_') . 'order_state')
            ->where('module_name = "' . $this->query->escape($module) . '"')
        ;

        return $this->query->build('unique_value');
    }

    /**
     * @description Get order payment
     *
     * @param int $id_order
     *
     * @return string
     */
    public function getPayplugOrderPayment($id_order)
    {
        $this->query
            ->select()
            ->fields('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_order_payment')
            ->where('id_order = ' . (int) $id_order)
        ;

        return $this->query->build('unique_value');
    }

    /**
     * @description Get all order payment for given id order
     *
     * @param int $id_order
     *
     * @return array
     */
    public function getPayplugOrderPayments($id_order)
    {
        $this->query
            ->select()
            ->fields('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_order_payment')
            ->where('id_order = ' . (int) $id_order)
        ;

        return $this->query->build();
    }

    /**
     * @description Get the current Order State Id for a given Order ID
     *
     * @param bool $id_order
     *
     * @return false|int
     */
    public function getCurrentOrderState($id_order = false)
    {
        if (!$id_order) {
            return false;
        }

        $this->query
            ->select()
            ->fields('current_state')
            ->from($this->constant->get('_DB_PREFIX_') . 'orders')
            ->where('id_order = ' . (int) $id_order)
        ;

        return $this->query->build('unique_value');
    }

    /**
     * @description get the undefined order state on an history
     *
     * @param int $orderId
     *
     * @return array
     */
    public function getUndefinedOrderHistory($orderId = false)
    {
        if (!$orderId || !is_int($orderId)) {
            return [];
        }

        $order_history_states = $this->query
            ->select()
            ->fields('oh.id_order_state, osl.name')
            ->from($this->constant->get('_DB_PREFIX_') . 'order_history', 'oh')
            ->leftJoin(
                $this->constant->get('_DB_PREFIX_') . 'order_state_lang',
                'osl',
                'osl.`id_order_state` = oh.`id_order_state`'
            )
            ->where('oh.id_order = ' . (int) $orderId)
            ->where('osl.id_lang = ' . (int) $this->context->language->id)
            ->build()
        ;

        if (empty($order_history_states)) {
            return [];
        }

        foreach ($order_history_states as $key => &$state) {
            $type = $this->orderState->getType((int) $state['id_order_state']);
            $state['type'] = $type;
            if (!$type || 'undefined' != $type) {
                unset($order_history_states[$key]);

                continue;
            }
            $update_link_params = [
                'updateorder_state' => '',
                'id_order_state' => $state['id_order_state'],
            ];
            $state['updateLink'] = $this->dependencies->adminClass->getAdminUrl('AdminStatuses', $update_link_params);
        }

        return $order_history_states;
    }
}
