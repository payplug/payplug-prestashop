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

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderAction
{
    private $configuration;
    private $dependencies;
    private $logger;
    private $order;
    private $plugin;
    private $validate_adapter;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Add an order from a resource id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function createAction($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('OrderAction::createAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        // Get the payment from database
        $payment_tab = $this->plugin
            ->getPaymentRepository()
            ->getByResourceId($resource_id);
        if (empty($payment_tab)) {
            $this->logger->addLog('OrderAction::createAction - Can\'t retrieve resource from database', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t retrieve resource from database',
            ];
        }

        // Get the resource form API
        $is_installment = $this->dependencies->getValidators()['payment']->isInstallment($resource_id)['result'];
        $retrieve = $is_installment
            ? $this->dependencies->apiClass->retrieveInstallment($resource_id)
            : $this->dependencies->apiClass->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('OrderAction::createAction - Can\'t retrieve resource from api', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t retrieve resource from api',
            ];
        }
        $resource = $retrieve['resource'];

        if (isset($resource->failure) && null !== $resource->failure) {
            $this->logger->addLog('OrderAction::createAction - Retrieve resource has failure', 'error');
            $this->logger->addLog('Resource failure: ' . $resource->failure->message, 'error');

            return [
                'result' => false,
                'message' => 'Resource failure: ' . $resource->failure->message,
            ];
        }

        // Get the related Cart
        $cart = $this->plugin
            ->getCart()
            ->get((int) $payment_tab['id_cart']);
        if (!$this->validate_adapter->validate('isLoadedObject', $cart)) {
            $this->logger->addLog('OrderAction::createAction - $cart should be a valid Cart Object', 'error');

            return [
                'result' => false,
                'message' => '$cart should be a valid Cart Object',
            ];
        }

        // Get the related Customer
        $customer = $this->plugin
            ->getCustomer()
            ->get((int) $cart->id_customer);
        if (!$this->validate_adapter->validate('isLoadedObject', $customer)) {
            $this->logger->addLog('OrderAction::createAction - $customer should be a valid Customer Object', 'error');

            return [
                'result' => false,
                'message' => '$customer should be a valid Customer Object',
            ];
        }

        // Create the payment from given payment_tab
        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($payment_tab['method']);

        // Get props from PaymentMethod
        $order_tab = $payment_method->getOrderTab($resource);
        if (empty($order_tab)) {
            $this->logger->addLog('OrderAction::createAction - $order_tab must be an non empty array', 'error');

            return [
                'result' => false,
                'message' => '$order_tab must be an non empty array',
            ];
        }

        $secure_key = isset($cart->secure_key) && $cart->secure_key
            ? $cart->secure_key
            : (isset($customer->secure_key) && $customer->secure_key ? $customer->secure_key : '');

        // Check if this notification is the first of the day
        $is_first_order = empty($this->plugin->getOrderRepository()->getCurrentOrders());

        // Create the order
        $module = $this->plugin
            ->getModule()
            ->getInstanceByName($this->dependencies->name);

        $can_save_card = $this->dependencies
            ->getValidators()['payment']
            ->canSaveCard($resource);
        if ($can_save_card) {
            $this->dependencies
                ->getPlugin()
                ->getCardAction()
                ->saveAction($resource);
        }

        try {
            $module->validateOrder(
                $cart->id,
                $order_tab['order_state'],
                $order_tab['amount'],
                $order_tab['module_name'],
                null,
                ['transaction_id' => $resource_id],
                (int) $cart->id_currency,
                false,
                $secure_key // get from cart or customer
            );
        } catch (\Exception $exception) {
            $this->logger->addLog('OrderAction::createAction - Order cannot be validated: ' . $exception->getMessage(), 'error');

            return [
                'result' => false,
                'message' => 'Order cannot be validated: ' . $exception->getMessage(),
            ];
        }

        // Get the related Order
        $order = $this->plugin
            ->getOrder()
            ->get((int) $module->currentOrder);
        if (!$this->validate_adapter->validate('isLoadedObject', $order)) {
            $this->logger->addLog('OrderAction::createAction - $order should be a valid Order Object', 'error');

            return [
                'result' => false,
                'message' => '$order should be a valid Order Object',
            ];
        }

        // Post process
        $post_process = $payment_method->postProcessOrder($resource, $order);
        if (!$post_process) {
            $this->logger->addLog('OrderAction::createAction - Can\'t post process the order', 'error');
        }

        // Add prestashop OrderPayment
        $order_payments = $order->getOrderPayments();
        if (!$order_payments) {
            $order->addOrderPayment($order_tab['amount'], null, $resource_id);
        }

        // Check number of order using this cart
        $res_nb_orders = $this->plugin
            ->getOrderRepository()
            ->getByIdCart((int) $cart->id);
        if (!$res_nb_orders) {
            $this->logger->addLog('OrderAction::createAction - No order created for the given cart id', 'error');

            return [
                'result' => false,
                'message' => 'No order created for the given cart id',
            ];
        } elseif (count($res_nb_orders) > 1) {
            $this->logger->addLog('OrderAction::createAction - ' . count($res_nb_orders) . ' orders created for the given cart id', 'error');
        }

        // Before ending process, if this is the first order of the days, we send the telemetries
        if ($is_first_order) {
            $this->plugin
                ->getMerchantTelemetryAction()
                ->sendAction('notification');
        }

        return [
            'result' => true,
            'id_order' => $order->id,
            'message' => '',
        ];
    }

    /**
     * @description Update an order from a resource id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function updateAction($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('OrderAction::updateAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        // Get the payment from database
        $payment_tab = $this->plugin
            ->getPaymentRepository()
            ->getByResourceId($resource_id);
        if (empty($payment_tab)) {
            $this->logger->addLog('OrderAction::updateAction - Can\'t retrieve resource from database', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t retrieve resource from database',
            ];
        }

        // Get the resource form API
        $retrieve = $this->dependencies->apiClass->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('OrderAction::updateAction - Can\'t retrieve resource from api', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t retrieve resource from api',
            ];
        }

        $resource = $retrieve['resource'];
        if (!(bool) $resource->is_paid) {
            return [
                'result' => true,
                'message' => 'The payment is not paid yet.',
            ];
        }

        $order = $this->order
            ->get((int) $resource->metadata['Order']);
        if (!$this->dependencies
            ->getPlugin()
            ->getValidate()
            ->validate('isLoadedObject', $order)) {
            $this->logger->addLog('OrderAction::updateAction - Order cannot be loaded', 'error');

            return [
                'result' => false,
                'message' => 'Order cannot be loaded',
            ];
        }

        $type = $this->plugin
            ->getPayplugOrderStateRepository()
            ->getTypeByIdOrderState((int) $order->current_state);
        $type = $type ?: 'undefined';

        if ('undefined' == $type) {
            $this->dependencies
                ->getPlugin()
                ->getPayplugOrderStateRepository()
                ->setOrderState((int) $order->current_state, $type);
        }

        $no_action_state = ['cancelled', 'error', 'expired', 'nothing', 'paid', 'refund', 'undefined'];
        if (in_array($type, $no_action_state)) {
            $this->logger->addLog('OrderAction::updateAction - No action required, order state type is ' . $type);

            return [
                'result' => true,
                'id_order' => $order->id,
                'message' => 'No action required, order state type is ' . $type,
            ];
        }

        $order_states = $this->dependencies
            ->getPlugin()
            ->getOrderClass()
            ->getOrderStates($resource->is_live);

        $new_order_state = $this->plugin
            ->getOrderClass()
            ->getOrderStateFromResource($resource);

        if (!$new_order_state['result']) {
            if (!$this->plugin
                ->getOrderClass()
                ->updateOrderState($order, (int) $order_states[$new_order_state['status']])) {
                $this->logger->addLog('OrderAction::updateAction - Can\'t update order state for given state: ' . $new_order_state['status'], 'error');

                return [
                    'result' => false,
                    'id_order' => $order->id,
                    'message' => 'Can\'t update order state',
                ];
            }

            return [
                'result' => true,
                'id_order' => $order->id,
                'message' => 'Order state will be: ' . $order_states[$new_order_state['status']],
            ];
        }

        // Get associated transaction
        $order_payments = $order->getOrderPayments();
        if (!$order_payments) {
            if (!$order->addOrderPayment($resource->amount / 100, null, $resource->id)) {
                $this->logger->addLog('OrderAction::updateAction - Can set order payment for given order', 'error');

                return [
                    'result' => false,
                    'message' => 'Can set order payment for given order',
                ];
            }
        }

        // Then update the order state
        if (!$this->plugin
            ->getOrderClass()
            ->updateOrderState($order, (int) $order_states[$new_order_state['status']])) {
            $this->logger->addLog('OrderAction::updateAction - Can\'t update order state', 'error');

            return [
                'result' => false,
                'id_order' => $order->id,
                'message' => 'Can\'t update order state',
            ];
        }

        return [
            'result' => true,
            'id_order' => $order->id,
            'message' => 'Order update with state: ' . $order_states[$new_order_state['status']],
        ];
    }

    // todo: add coverage to this method
    public function renderDetail($order_id = 0)
    {
        $this->setParameters();

        $order_details = [];

        if (!is_int($order_id) || !$order_id) {
            $this->logger->addLog('OrderAction::renderDetail - Invalid argument, $order_id must be a non null integer.', 'error');

            return $order_details;
        }

        $order = $this->order
            ->get((int) $order_id);
        if (!$this->validate_adapter->validate('isLoadedObject', $order)) {
            $this->logger->addLog('OrderAction::renderDetail - $order can\'t be retrieve.', 'error');

            return $order_details;
        }

        if ($order->module != $this->dependencies->name) {
            return $order_details;
        }

        // Retrieve the resource from database
        $payment_tab = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $order->id_cart);
        if (empty($payment_tab)) {
            return $order_details;
        }

        $order_details = [
            'logo_url' => $this->dependencies->getPlugin()->getConstant()->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/payplug.svg',
            'admin_ajax_url' => $this->dependencies->adminClass->getAdminAjaxUrl('AdminModules', (int) $order->id),
            'order' => $order,
            'refund' => false,
            'refunded' => false,
            'update' => false,
        ];

        // Create the detail from given payment_tab
        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($payment_tab['method']);

        if (empty($payment_method)) {
            return [];
        }

        // Check order state history
        $undefined_history_states = $this->dependencies->orderClass->getUndefinedOrderHistory((int) $order->id);
        if (!empty($undefined_history_states)) {
            $order_details['payplug_order_state_url'] = $this->dependencies
                ->getPlugin()
                ->getRoutes()
                ->getExternalUrl($this->context->language->iso_code)['order_state'];
            $order_details['undefined_history_states'] = $undefined_history_states;
        }

        // Get payment detail section
        $resource_detail = $payment_method->getResourceDetail($payment_tab['resource_id']);
        if (empty($resource_detail)) {
            return [];
        }

        $state_addons = 'live' == strtolower($resource_detail['mode']) ? '' : '_test';
        if ('installment' == $payment_tab['method']) {
            $order_details['installment'] = $resource_detail;
        } else {
            $order_details['payment'] = $resource_detail;
            $pending_order_state = (int) $this->configuration->getValue('order_state_pending' . $state_addons);
            $order_details['update'] = $pending_order_state == $order->current_state;
        }

        if (!isset($resource_detail['refund'])) {
            return $order_details;
        }

        // Get the refund section
        if ($resource_detail['refund']['available']) {
            $refunded_presta = $this->dependencies->refundClass->getTotalRefunded($order->id);
            $amount_suggested = \min($refunded_presta, $resource_detail['refund']['available']) - $resource_detail['refund']['refunded'];
            $amount_suggested = \number_format((float) $amount_suggested, 2);
            $amount_suggested = 0 <= $amount_suggested ? $amount_suggested : 0;

            $id_currency = (int) $this->dependencies
                ->getPlugin()
                ->getCurrency()
                ->getIdByIsoCode($resource_detail['currency']);
            $currency = $this->dependencies
                ->getPlugin()
                ->getCurrency()
                ->get((int) $id_currency);
            if (!$this->validate_adapter->validate('isLoadedObject', $currency)) {
                $this->logger->addLog('OrderAction::renderDetail - $currency is not a valid object.', 'error');

                return $order_details;
            }

            $order_details['refund'] = [
                'refunded' => $resource_detail['refund']['refunded'],
                'available' => $resource_detail['refund']['available'],
                'refunded_presta' => $refunded_presta,
                'suggested' => $amount_suggested,
                'mode' => \strtolower($resource_detail['mode']),
                'id' => $resource_detail['id'],
                'new_order_state' => (int) $this->configuration->getValue('order_state_refund' . $state_addons),
                'currency' => $currency,
                'disabled' => !(bool) $payment_method->refundable,
            ];
        } elseif ($resource_detail['refund']['is_refunded']) {
            $order_details['refunded'] = $resource_detail['refund']['refunded'];
        }

        return $order_details;
    }

    /**
     * @description Set needed object from dependencies
     */
    private function setParameters()
    {
        $this->plugin = $this->plugin ?: $this->dependencies
            ->getPlugin();
        $this->configuration = $this->configuration ?: $this->plugin
            ->getConfigurationClass();
        $this->logger = $this->logger ?: $this->plugin
            ->getLogger();
        $this->order = $this->order ?: $this->plugin
            ->getOrder();
        $this->validate_adapter = $this->validate_adapter ?: $this->plugin
            ->getValidate();
    }
}
