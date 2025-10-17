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

namespace PayPlug\classes;

use PayPlug\src\application\adapter\OrderStateAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderClass
{
    private $context;
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
    }

    /**
     * @param null $id_lang
     *
     * @return array
     */
    public function getOrderStates($id_lang = null)
    {
        if (null === $id_lang) {
            $id_lang = $this->context->language->id;
        }

        return OrderStateAdapter::getOrderStates($id_lang);
    }

    /**
     * @description get the undefined order state on an history
     *
     * @param int $orderId
     *
     * @return array
     */
    public function getUndefinedOrderHistory($orderId)
    {
        if (!$orderId || !is_int($orderId)) {
            return [];
        }

        $order_history_states = $this->dependencies
            ->getPlugin()
            ->getOrderStateRepository()
            ->getOrderHistory((int) $orderId, (int) $this->context->language->id);

        if (empty($order_history_states)) {
            return [];
        }

        $undefined_history_states = [];
        foreach ($order_history_states as $key => &$state) {
            $order_state = $this->dependencies
                ->getPlugin()
                ->getStateRepository()
                ->getBy('id_order_state', (int) $state['id_order_state']);
            $state['type'] = isset($order_state['type']) && $order_state['type'] ? $order_state['type'] : 'undefined';
            if (!$state['type'] || 'undefined' != $state['type']) {
                continue;
            }
            $update_link_params = [
                'updateorder_state' => '',
                'id_order_state' => $state['id_order_state'],
            ];
            $state['updateLink'] = $this->dependencies->adminClass->getAdminUrl('AdminStatuses', $update_link_params);
            $undefined_history_states[$state['id_order_state']] = $state;
        }

        return $undefined_history_states;
    }
}
