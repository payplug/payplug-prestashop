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

namespace PayPlug\src\actions;

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class HookAction
{
    public $context;
    public $dependencies;
    public $plugin;
    public $configuration;
    public $logger;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Create a payment link order from given order status and cart id
     *
     * @param int $order_state_id
     * @param int $id_cart
     *
     * @return bool
     */
    public function createPaymentLinkAction($order_state_id = 0, $id_cart = 0)
    {
        $this->setParameters();

        if (!is_int($order_state_id) || empty($order_state_id)) {
            $this->logger->addLog('HookAction::createPaymentLinkAction() - Invalid argument given, $order_state_id must be a non null integer.', 'critical');

            return false;
        }
        if (!is_int($id_cart) || empty($id_cart)) {
            $this->logger->addLog('HookAction::createPaymentLinkAction() - Invalid argument given, $id_cart must be a non null integer.', 'critical');

            return false;
        }

        // Check if merchant is logged
        $is_logged = (bool) $this->configuration->getValue('test_api_key') && (bool) $this->configuration->getValue('email');
        if (!$is_logged) {
            $this->logger->addLog('HookAction::createPaymentLinkAction() - Merchant should be logged to create payment link');

            return true;
        }

        $is_live_order_state = false;

        // Check the order state
        switch (true) {
            case $order_state_id == $this->configuration->getValue('order_state_email_link'):
                $is_live_order_state = true;
                // no break
            case $order_state_id == $this->configuration->getValue('order_state_email_link_test'):
                $method = 'email_link';

                break;
            case $order_state_id == $this->configuration->getValue('order_state_sms_link'):
                $is_live_order_state = true;
                // no break
            case $order_state_id == $this->configuration->getValue('order_state_sms_link_test'):
                $method = 'sms_link';

                break;
            default:
                $method = '';

                break;
        }

        if (!$method) {
            $this->logger->addLog('HookAction::createPaymentLinkAction() - Invalid order status to create payment link');

            return true;
        }

        // Check if merchant can create payment link on live
        $can_use_live = $this->configuration->getValue('live_api_key');
        if (!(bool) $can_use_live) {
            $this->logger->addLog('HookAction::createPaymentLinkAction() - Merchant should has no permission to create payment link in live mode');

            return true;
        }

        // Check if a valid payment resource already exists for given id cart
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $id_cart);
        if (!empty($stored_resource) && in_array($stored_resource['method'], ['email_link', 'sms_link'])) {
            $this->logger->addLog('HookAction::createPaymentLinkAction() - A stored payment already exist for this cart');

            return true;
        }

        // Check context variable
        $this->setContextFromCartId((int) $id_cart);

        // If the configuration differe the order state, we force the configuration to comply before create the payment
        $is_live_mode = !(bool) $this->configuration->getValue('sandbox_mode');
        if ($is_live_order_state != $is_live_mode) {
            $this->configuration->set('sandbox_mode', $is_live_order_state ? 0 : 1);
        }

        // We create payment resource
        $resource = $this->dependencies
            ->getPlugin()
            ->getPaymentAction()
            ->dispatchAction($method);
        if (empty($resource)) {
            $this->logger->addLog('HookAction::createPaymentLinkAction() - The payment can\'t be created', 'critical');

            return false;
        }

        // ...then we reset the configuration in case of needs
        if ($is_live_order_state != $is_live_mode) {
            $this->configuration->set('sandbox_mode', $is_live_mode ? 0 : 1);
        }

        return true;
    }

    /**
     * @description Auto capture payment
     *
     * @param object $order
     *
     * @return bool
     */
    public function autoCapturePaymentAction($order = null)
    {
        $this->setParameters();

        if (!is_object($order) || !$order->id) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - Invalid argument given, $order must be a non null object.', 'critical');

            return false;
        }

        $payment_methods = json_decode($this->configuration->getValue('payment_methods'), true);
        $can_use_deferred = (bool) $payment_methods['deferred'];

        if (!$can_use_deferred) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - deferred must be active to allow auto capture');

            return true;
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $order->id_cart);

        if ('installment' == $stored_resource['method']) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - auto capture is not compatible with installment plan.');

            return true;
        }

        // Check if resource can be capture
        $retrieve = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method'])
            ->retrieve($stored_resource['resource_id']);
        $resource = $retrieve['resource'];
        $payment_validator = $this->dependencies->getValidators()['payment'];
        $can_be_captured = empty($resource->failure)
            && !$resource->is_paid
            && $payment_validator->isDeferred($resource)['result']
            && !$payment_validator->isExpired($resource)['result'];

        if (!$can_be_captured) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - given resource can\'t be captured');

            return true;
        }

        return (bool) $this->dependencies
            ->getPlugin()
            ->getPaymentAction()
            ->captureAction($resource->id, (int) $order->id)['result'];
    }

    public function setParameters()
    {
        $this->plugin = $this->plugin ?: $this->dependencies->getPlugin();
        $this->configuration = $this->configuration ?: $this->plugin->getConfigurationClass();
        $this->logger = $this->logger ?: $this->plugin->getLogger();
    }

    public function setContextFromCartId($id_cart = 0)
    {
        if (!is_int($id_cart) || empty($id_cart)) {
            $this->logger->addLog('HookAction::setContextFromCartId() - Invalid argument given, $id_cart must be a non null integer.', 'critical');

            return false;
        }

        $this->context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();
        $this->context->cart = $this->context->cart ?: $this->dependencies
            ->getPlugin()
            ->getCart()
            ->get((int) $id_cart);
        $this->context->customer = $this->context->customer ?: $this->dependencies
            ->getPlugin()
            ->getCustomer()
            ->get((int) $this->context->cart->id_customer);
        $this->context->currency = $this->context->currency ?: $this->dependencies
            ->getPlugin()
            ->getCustomer()
            ->get((int) $this->context->cart->id_currency);
    }
}
