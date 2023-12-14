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

namespace PayPlug\src\models\classes;

class Order
{
    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Get the appropriate order state for a given resource
     *
     * @param null $resource
     *
     * @return array
     */
    public function getOrderStateFromResource($resource = null)
    {
        if (!is_object($resource) || !$resource) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Order::getOrderStateFromResource - Invalid argument, $resource must be non null object.', 'error');

            return [
                'result' => false,
                'status' => 'error',
            ];
        }

        // Define if payment is oney resource
        $oney_payment_methods = [
            'oney_x3_with_fees',
            'oney_x4_with_fees',
            'oney_x3_without_fees',
            'oney_x4_without_fees',
        ];
        $is_oney = false;
        if (isset($resource->payment_method, $resource->payment_method['type'])) {
            $is_oney = in_array($resource->payment_method['type'], $oney_payment_methods);
        }

        // Check if order is refused by oney
        if ($is_oney && $this->dependencies->getValidators()['payment']->isFailed($resource)['result']) {
            return [
                'result' => false,
                'status' => 'cancelled',
            ];
        }

        $is_expired = !$is_oney
            && $this->dependencies->getValidators()['payment']->isDeferred($resource)['result']
            && $this->dependencies->getValidators()['payment']->isExpired($resource)['result'];

        // CHeck if payment capture is expired
        if ($is_expired) {
            return [
                'result' => false,
                'status' => 'expired',
            ];
        }

        // Check if payment has failure
        if ($this->dependencies->getValidators()['payment']->isFailed($resource)['result']) {
            return [
                'result' => false,
                'status' => 'error',
            ];
        }

        // Paid but one or multiple products out of stock
        $order = $this->dependencies
            ->getPlugin()
            ->getOrder()
            ->get((int) $resource->metadata['Order']);
        $order_details = $order->getOrderDetailList();
        foreach ($order_details as $order_detail) {
            if ($this->dependencies->getPlugin()->getConfiguration()->get('PS_STOCK_MANAGEMENT')
                && 0 >= $order_detail['product_quantity_in_stock']) {
                return [
                    'result' => true,
                    'status' => 'oos_paid',
                ];
            }
        }

        return [
            'result' => true,
            'status' => 'paid',
        ];
    }

    /**
     * @description Update the current state of an order
     *
     * @param null $order
     * @param int $new_order_state
     *
     * @return bool
     */
    public function updateOrderState($order = null, $new_order_state = 0)
    {
        if (!is_object($order) || !$order) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Order::updateOrderState - Invalid argument, $order must be non null object.', 'error');

            return false;
        }

        if (!is_int($new_order_state) || !$new_order_state) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Order::updateOrderState - Invalid argument, $new_order_state must be non null integer.', 'error');

            return false;
        }

        if ($new_order_state == $order->current_state) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Order::updateOrderState - New order state and current one are the same.', 'notice');

            return true;
        }

        $order_history = $this->dependencies
            ->getPlugin()
            ->getOrderHistory()
            ->get();
        $order_history->id_order = (int) $order->id;
        $order_history->changeIdOrderState((int) $new_order_state, $order->id, true);

        if (!$order_history->save()) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Order::updateOrderState - Can not save order history.', 'error');

            return false;
        }

        $order->current_state = $order_history->id_order_state;

        if (!$order->update()) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Order::updateOrderState - Can not update order.', 'error');

            return false;
        }

        return true;
    }
}
