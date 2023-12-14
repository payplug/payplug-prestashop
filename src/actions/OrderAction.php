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
            $this->logger->addLog('OrderAction::createAction - Can not retrieve resource from database', 'error');

            return [
                'result' => false,
                'message' => 'Can not retrieve resource from database',
            ];
        }

        // Get the resource form API
        $is_installment = $this->dependencies->getValidators()['payment']->isInstallment($resource_id)['result'];
        $retrieve = $is_installment
            ? $this->dependencies->apiClass->retrieveInstallment($resource_id)
            : $this->dependencies->apiClass->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('OrderAction::createAction - Can not retrieve resource from api', 'error');

            return [
                'result' => false,
                'message' => 'Can not retrieve resource from api',
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
            $this->logger->addLog('OrderAction::createAction - Can not post process the order', 'error');
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
            $this->logger->addLog('OrderAction::updateAction - Can not retrieve resource from database', 'error');

            return [
                'result' => false,
                'message' => 'Can not retrieve resource from database',
            ];
        }

        // Get the resource form API
        $retrieve = $this->dependencies->apiClass->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('OrderAction::updateAction - Can not retrieve resource from api', 'error');

            return [
                'result' => false,
                'message' => 'Can not retrieve resource from api',
            ];
        }

        $resource = $retrieve['resource'];
        if (!(bool) $resource->is_paid) {
            return [
                'result' => true,
                'message' => 'The payment is not paid yet.',
            ];
        }

        $order = $this->plugin
            ->getOrder()
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

        $state_addons = $resource->is_live ? '' : '_test';
        // todo: We should move this order state collection in the appropriate service
        $order_states = [
            'cancelled' => $this->configuration->getValue('order_state_canceled' . $state_addons),
            'error' => $this->configuration->getValue('order_state_error' . $state_addons),
            'expired' => $this->configuration->getValue('order_state_exp' . $state_addons),
            'oos_paid' => $this->dependencies->getPlugin()->getConfiguration()->get('PS_OS_OUTOFSTOCK_PAID'),
            'paid' => $this->configuration->getValue('order_state_paid' . $state_addons),
        ];

        $new_order_state = $this->plugin
            ->getOrderClass()
            ->getOrderStateFromResource($resource);

        if (!$new_order_state['result']) {
            if (!$this->plugin
                ->getOrderClass()
                ->updateOrderState($order, (int) $order_states[$new_order_state['status']])) {
                $this->logger->addLog('OrderAction::updateAction - Can not update order state', 'error');

                return [
                    'result' => false,
                    'id_order' => $order->id,
                    'message' => 'Can not update order state',
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
            $this->logger->addLog('OrderAction::updateAction - Can not update order state', 'error');

            return [
                'result' => false,
                'id_order' => $order->id,
                'message' => 'Can not update order state',
            ];
        }

        return [
            'result' => true,
            'id_order' => $order->id,
            'message' => 'Order update with state: ' . $order_states[$new_order_state['status']],
        ];
    }

    public function stopAction()
    {
    }

    public function renderDetail()
    {
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
        $this->validate_adapter = $this->validate_adapter ?: $this->plugin
            ->getValidate();
    }
}
