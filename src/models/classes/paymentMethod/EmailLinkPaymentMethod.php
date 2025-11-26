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

namespace PayPlug\src\models\classes\paymentMethod;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EmailLinkPaymentMethod extends StandardPaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'email_link';
        $this->force_resource = true;
    }

    /**
     * @description Get option for given configuration
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getOption($current_configuration = [])
    {
        return [];
    }

    /**
     * @description Get the payment tab required to generate a resource payment.
     *
     * @return array
     */
    public function getPaymentTab()
    {
        $payment_tab = $this->getDefaultPaymentTab();
        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $payment_tab['billing']['landline_phone_number'] = $payment_tab['billing']['landline_phone_number'] ?: $payment_tab['shipping']['landline_phone_number'];
        $payment_tab['billing']['mobile_phone_number'] = $payment_tab['billing']['mobile_phone_number'] ?: $payment_tab['shipping']['mobile_phone_number'];
        $payment_tab['hosted_payment']['sent_by'] = 'EMAIL';
        $payment_tab['metadata']['Order'] = $this->tools->tool('getValue', 'id_order');

        // If one click is activated, we disable it in the payment link process
        if (isset($payment_tab['allow_save_card']) && $payment_tab['allow_save_card']) {
            $payment_tab['allow_save_card'] = false;
        }

        // If deferred payment is activated, we disable it or the resource will be considere as expired at his creation
        if (isset($payment_tab['authorized_amount']) && $payment_tab['authorized_amount']) {
            $payment_tab['amount'] = $payment_tab['authorized_amount'];
            unset($payment_tab['authorized_amount']);
        }

        // If display mode is integrated, we have to disable it to ensure the validation of the payment page
        if (isset($payment_tab['integration']) && $payment_tab['integration']) {
            unset($payment_tab['integration']);
        }

        // After the payment validation, the customer should not be redirected
        if (isset($payment_tab['hosted_payment']['return_url']) && $payment_tab['hosted_payment']['return_url']) {
            unset($payment_tab['hosted_payment']['return_url']);
        }

        return $payment_tab;
    }

    /**
     * @description Get the resource detail
     *
     * todo: add coverage to this method
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('EmailLinkPaymentMethod::getResourceDetail() - Invalid argument given, $resource_id must be a non empty string.');

            return [];
        }

        $resource_details = parent::getResourceDetail($resource_id);
        if (empty($resource_details)) {
            return $resource_details;
        }

        // If status is paid but order state is pending then update the current state
        if ('paid' == $resource_details['status_code']) {
            $order_id = $this->tools->tool('getValue', 'id_order');
            $is_live = 'TEST' != $resource_details['mode'];
            $this->updateOrderStateFromPendingToPaid((int) $order_id, (bool) $is_live);
        }

        return $resource_details;
    }

    /**
     * @param mixed $payment_options
     *
     * @return array
     */
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        return $payment_options;
    }

    /**
     * @description Update the order state if current state is pending
     *
     * todo: add coverage to this method
     *
     * @param int $order_id
     * @param bool $is_live
     *
     * @return bool
     */
    protected function updateOrderStateFromPendingToPaid($order_id = 0, $is_live = true)
    {
        $this->setParameters();

        if (!is_int($order_id) || !$order_id) {
            $this->logger->addLog('EmailLinkPaymentMethod::updateOrderState() - Invalid argument given, $order_id must be a non null integer.', 'error');

            return false;
        }

        if (!is_bool($is_live)) {
            $this->logger->addLog('EmailLinkPaymentMethod::updateOrderState() - Invalid argument given, $is_live must be a valid boolean.', 'error');

            return false;
        }

        $order = $this->dependencies->getPlugin()
            ->getOrder()
            ->get((int) $order_id);

        if (!$this->validate_adapter->validate('isLoadedObject', $order)) {
            $this->logger->addLog('EmailLinkPaymentMethod::updateOrderState() - Invalid argument given, $order getted must be a valid object.', 'error');

            return false;
        }

        $state_addons = $is_live ? '' : '_test';
        $pending_os = $this->configuration->getValue('order_state_email_link' . $state_addons);

        if ($order->getCurrentState() != $pending_os) {
            return true;
        }

        $paid_os = $this->configuration->getValue('order_state_paid' . $state_addons);
        $update_order_history = $this->dependencies
            ->getPlugin()
            ->getOrderClass()
            ->updateOrderState($order, (int) $paid_os);

        // If order history well update, then we force the reload
        if (!$update_order_history) {
            $this->logger->addLog('EmailLinkPaymentMethod::updateOrderState() - Failed to update order state', 'error');

            return false;
        }

        $parameters = ['vieworder' => 1, 'id_order' => (int) $order->id];
        $link_order = $this->context->link->getAdminLink('AdminOrders', true, [], $parameters);

        return $this->tools->tool('redirectAdmin', $link_order);
    }
}
