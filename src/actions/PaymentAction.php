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

class PaymentAction
{
    private $available_payment = [
        'amex',
        'applepay',
        'bancontact',
        'ideal',
        'installment',
        'mybank',
        'email_link',
        'sms_link',
        'one_click',
        'oney',
        'satispay',
        'standard',
    ];
    private $context;
    private $dependencies;
    private $logger;
    private $plugin;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Process on the abort of a installment
     *
     * @param string $resource_id
     * @param int $order_id
     *
     * @return array
     */
    public function abortAction($resource_id = '', $order_id = 0)
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentAction::abortAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        if (!is_int($order_id) || !$order_id) {
            $this->logger->addLog('PaymentAction::abortAction - Invalid argument, $order_id must be a non null integer.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $order_id must be a non null integer.',
            ];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);
        if (empty($stored_resource)) {
            $this->logger->addLog('PaymentAction::abortAction - Can\'t get the stored payment resource.', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t abort the payment.',
            ];
        }
        $payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method']);
        $abort = $payment_method->abort($resource_id);
        if (!$abort['result']) {
            $this->logger->addLog('PaymentAction::abortAction - Can\'t abort the payment.', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t abort the payment.',
            ];
        }

        $retrieve = $payment_method->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentAction::abortAction - Can\'t retrieve the aborted payment.', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t retrieve the aborted payment.',
            ];
        }
        $installment = $retrieve['resource'];

        $order = $this->plugin
            ->getOrder()
            ->get((int) $order_id);

        if (!$this->plugin
            ->getValidate()
            ->validate('isLoadedObject', $order)) {
            $this->logger->addLog('PaymentAction::abortAction - The related Order object is not valid.', 'error');

            return [
                'result' => false,
                'message' => 'The related Order object is not valid.',
            ];
        }

        $new_state = (int) $this->plugin
            ->getConfiguration()
            ->get('PS_OS_CANCELED');

        $this->dependencies->getPlugin()
            ->getOrderClass()
            ->updateOrderState($order, (int) $new_state);

        $step_to_update = [];
        foreach ($retrieve['schedule'] as $key => $schedule) {
            $schedule_resource_id = '';
            if ($schedule['resource']) {
                $schedule_resource_id = $schedule['resource']->id;
                $status = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('standard')
                    ->getPaymentStatus($schedule['resource'])['id_status'];
            } else {
                if (1 == (int) $installment->is_active) {
                    $status = 6; // ongoing
                } else {
                    $status = 7; // cancelled
                }
            }
            $step = ($key + 1) . '/' . count($installment->schedule);
            $step_to_update[$step] = [
                'pay_id' => $schedule_resource_id,
                'status' => (int) $status,
            ];
        }

        $stored_resource = $this->plugin
            ->getPaymentRepository()
            ->getBy('resource_id', $installment->id);
        $schedules = json_decode((string) $stored_resource['schedules'], true);
        foreach ($schedules as &$schedule) {
            $step = $schedule['step'];
            if (array_key_exists($step, $step_to_update)) {
                $schedule['pay_id'] = $step_to_update[$step]['pay_id'];
                $schedule['status'] = $step_to_update[$step]['status'];
            }
        }

        return [
            'result' => (bool) $this->plugin
                ->getPaymentRepository()
                ->updateBy('resource_id', $installment->id, [
                    'schedules' => json_encode($schedules),
                ]),
            'message' => '',
        ];
    }

    /**
     * @description Process on the capture of a deferred payment
     *
     * @param string $resource_id
     * @param int $order_id
     *
     * @return array
     */
    public function captureAction($resource_id = '', $order_id = 0)
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentAction::captureAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        if (!is_int($order_id) || !$order_id) {
            $this->logger->addLog('PaymentAction::captureAction - Invalid argument, $order_id must be a non null integer.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $order_id must be a non null integer.',
            ];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);
        if (empty($stored_resource)) {
            $this->logger->addLog('PaymentAction::captureAction - Can\'t get the stored payment resource.', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t capture the payment.',
            ];
        }
        $payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method']);
        $capture = $payment_method->capture($resource_id);
        if (!$capture['result']) {
            $this->logger->addLog('PaymentAction::captureAction - Can\'t capture the payment.', 'error');

            return [
                'result' => false,
                'message' => 'Can\'t capture the payment.',
            ];
        }

        $payment = $capture['resource'];
        if (null !== $payment->card->id) {
            $this->plugin
                ->getCardAction()
                ->saveAction($payment);
        }

        $state_addons = ($payment->is_live ? '' : '_test');
        $new_state = (int) $this->plugin
            ->getConfigurationClass()
            ->getValue('order_state_paid' . $state_addons);

        $order = $this->plugin
            ->getOrder()
            ->get((int) $order_id);
        if (!$this->plugin
            ->getValidate()
            ->validate('isLoadedObject', $order)) {
            $this->logger->addLog('PaymentAction::captureAction - The related Order object is not valid.', 'error');

            return [
                'result' => false,
                'message' => 'The related Order object is not valid.',
            ];
        }

        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            $create_queue = $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->hydrateAction((int) $order->id_cart, $resource_id);
            if (!$create_queue['result']) {
                $this->logger->addLog('PaymentAction::captureAction - An error occurred on queue creation', 'error');

                return [
                    'result' => false,
                    'message' => 'An error occurred on queue creation',
                ];
            }
        } else {
            if (!$this->dependencies->cartClass->createLockFromCartId((int) $order->id_cart)) {
                $this->logger->addLog('PaymentAction::captureAction - An error occured on lock creation', 'error');

                return [
                    'result' => false,
                    'message' => 'An error occured on lock creation',
                ];
            }
        }

        $this->dependencies
            ->getPlugin()
            ->getOrderClass()
            ->updateOrderState($order, (int) $new_state);

        if ($this->dependencies->configClass->isValidFeature('feature_queueing_system')) {
            $update_queue = (bool) $this->dependencies
                ->getPlugin()
                ->getQueueAction()
                ->updateAction((int) $order->id_cart)['result'];

            if ($update_queue) {
                $this->logger->addLog('PaymentAction::captureAction - Queue update succeeded for cart ID: ' . $order->id_cart, 'notice');
            } else {
                $this->logger->addLog('PaymentAction::captureAction - Queue entry cannot be updated for cart ID: ' . $order->id_cart, 'error');
            }
        } else {
            $delete_lock = $this->plugin
                ->getLockRepository()
                ->deleteBy('id_cart', (int) $order->id_cart);
            if ($delete_lock) {
                $this->logger->addLog('PaymentAction::captureAction - Lock deletion succeeded for cart ID: ' . $order->id_cart, 'notice');
            } else {
                $this->logger->addLog('PaymentAction::captureAction - Lock cannot be deleted for cart ID: ' . $order->id_cart, 'error');
            }
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Process on the creation of a payment
     *
     * @param string $method
     * @param array $payment_tab
     *
     * @return array
     */
    public function createAction($method = '', $payment_tab = [])
    {
        $this->setParameters();

        if (!is_string($method) || !$method) {
            $this->logger->addLog('PaymentAction::createAction - Invalid argument, $method must be a non empty string.', 'error');

            return [];
        }

        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->logger->addLog('PaymentAction::createAction - Invalid argument, $payment_tab must be a non empty array.', 'error');

            return [];
        }

        $cart_id = $this->plugin
            ->getContext()
            ->get()->cart->id;

        // If a payment exists, we try to cancel it and remove from database.
        $resource = $this->plugin
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $cart_id);
        if (!empty($resource)) {
            $payment_method = $this->plugin
                ->getPaymentMethodClass()
                ->getPaymentMethod($resource['method']);
            $removed = $this->removeAction($resource['resource_id'], $payment_method->cancellable);
            if (!$removed) {
                $this->logger->addLog('PaymentAction::createAction - Stored resource can\'t be remove.', 'error');

                return [];
            }
        }

        // Create the payment from given payment_tab
        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($method);

        $resource = $payment_method->saveResource($payment_tab);
        if (!$resource['result']) {
            $this->logger->addLog('PaymentAction::createAction - Resource can\'t be created from given tab.', 'error');

            return [];
        }

        // Generate the hash and create payment in database
        $payment_hash = $payment_method->getPaymentMethodHash($payment_tab, $resource['resource']->is_live);
        $parameters = [
            'resource_id' => $resource['resource']->id,
            'is_live' => $resource['resource']->is_live,
            'method' => $method,
            'id_cart' => (int) $cart_id,
            'cart_hash' => $payment_hash,
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        $save_hash = $this->plugin
            ->getPaymentRepository()
            ->createEntity($parameters);
        if (!$save_hash) {
            $this->logger->addLog('PaymentAction::createAction - Payment method hash can\'t be generated.', 'error');

            return [];
        }

        switch ($method) {
            case 'applepay':
                return $resource;
            case 'installment':
                $payment_method->addInstallmentSchedules($resource);
                // no break
            default:
                return $payment_method->getReturnUrl();
        }
    }

    /**
     * @description Process on
     *
     * @param string $method
     * @param bool $force
     *
     * @return array
     */
    public function dispatchAction($method = '', $force = false)
    {
        $this->setParameters();

        if (!is_string($method) || !$method) {
            $this->logger->addLog('PaymentAction::dispatchAction - Invalid argument, $method must be a string.', 'error');

            return [];
        }

        if (!is_bool($force)) {
            $this->logger->addLog('PaymentAction::dispatchAction - Invalid argument, $force must be a boolean.', 'error');

            return [];
        }

        if (!in_array($method, $this->available_payment)) {
            $this->logger->addLog('PaymentAction::dispatchAction - Invalid argument, $method given is not expected.', 'error');

            return [];
        }

        $payment_methods = $this->plugin
            ->getConfigurationClass()
            ->getValue('payment_methods');
        $payment_methods = json_decode($payment_methods, true);

        if (!$force) {
            switch ($method) {
                case 'one_click':
                    return [
                        'return_url' => 'index.php?controller=order&step=3&embedded=1'
                            . '&pc=' . $this->plugin
                                ->getTools()
                                ->tool('getValue', 'pc')
                            . '&def=' . (int) $payment_methods['deferred']
                            . '&modulename=' . $this->dependencies->name,
                    ];
                case 'amex':
                case 'installment':
                case 'standard':
                    if ('redirect' != (string) $this->plugin
                        ->getConfigurationClass()
                        ->getValue('embedded_mode')) {
                        return [
                            'return_url' => 'index.php?controller=order&step=3&embedded=1'
                                . ('installment' == $method ? '&inst=1' : '')
                                . ('amex' == $method ? '&amex=1' : '')
                                . ('amex' != $method && $payment_methods['deferred'] ? '&def=1' : '')
                                . '&modulename=' . $this->dependencies->name,
                        ];
                    }

                    break;
                default:
                    break;
            }
        }

        // Generate payment tab from proper payment method
        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($method);

        $payment_tab = $payment_method->getPaymentTab();

        if (empty($payment_tab)) {
            $this->logger->addLog('PaymentAction::dispatchAction - Cannot generate payment tab.', 'error');

            return [];
        }

        // Check if payment already exists or if resource creation is forced
        $force_resource_creation = $payment_method->force_resource;
        $cart_id = $this->plugin
            ->getContext()
            ->get()->cart->id;
        $stored_resource = $this->plugin
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $cart_id);

        $should_create_resource = $force_resource_creation
            || empty($stored_resource)
            || !$payment_method->isValidResource()
            || $stored_resource['method'] != $method;

        if ($should_create_resource) {
            return $this->createAction($method, $payment_tab);
        }

        return $this->retrieveAction($stored_resource, $payment_tab);
    }

    /**
     * @description Refund a payment resource
     *
     * @param string $resource_id
     * @param int $amount
     * @param int $id_customer
     * @param int $id_order
     * @param bool $update_order_state
     *
     * @return array
     */
    public function refundAction($resource_id = '', $amount = 0, $id_customer = 0, $id_order = 0, $update_order_state = false)
    {
        $this->setParameters();

        $translations = $this->plugin
            ->getTranslationClass()
            ->getRefundTranslations();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentAction::refundAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => $translations['error']['format'],
            ];
        }

        if (!is_int($amount) || !$amount) {
            $this->logger->addLog('PaymentAction::refundAction - Invalid argument, $amount must be a non null integer.', 'error');

            return [
                'result' => false,
                'message' => $translations['error']['format'],
            ];
        }

        if (!is_int($id_customer) || !$id_customer) {
            $this->logger->addLog('PaymentAction::refundAction - Invalid argument, $id_customer must be a non null integer.', 'error');

            return [
                'result' => false,
                'message' => $translations['error']['default'],
            ];
        }

        if (!is_int($id_order) || !$id_order) {
            $this->logger->addLog('PaymentAction::refundAction - Invalid argument, $id_order must be a non null integer.', 'error');

            return [
                'result' => false,
                'message' => $translations['error']['default'],
            ];
        }

        if (!is_bool($update_order_state)) {
            $this->logger->addLog('PaymentAction::refundAction - Invalid argument, $update_order_state must be valid boolean.', 'error');

            return [
                'result' => false,
                'message' => $translations['error']['default'],
            ];
        }

        // Get the stored resource
        $stored_resource = $this->plugin
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);
        if (empty($stored_resource)) {
            $this->logger->addLog('PaymentAction::refundAction - No stored payment found for given resource ID.', 'error');

            return [
                'result' => false,
                'message' => $translations['error']['default'],
            ];
        }

        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method']);

        // Get the refundable amount for the getted resource ID
        $refundable_amount = $payment_method->getRefundableAmount($stored_resource['resource_id']);

        // Check if given amount to refund is valid
        $is_refundable_amount = $this->dependencies->getValidators()['payment']->isRefundableAmount(
            (int) $amount,
            (int) $refundable_amount
        );
        if (!$is_refundable_amount['result']) {
            $this->logger->addLog('PaymentAction::refundAction - ' . $translations['error'][$is_refundable_amount['code']], 'error');

            return [
                'result' => false,
                'message' => $translations['error'][$is_refundable_amount['code']],
            ];
        }

        $metadata = [
            'ID Client' => (int) $id_customer,
            'reason' => 'Refunded with Prestashop',
        ];

        $refund = $payment_method->refund($resource_id, $amount, $metadata);
        if (!$refund['result']) {
            return [
                'result' => false,
                'message' => $translations['error']['default'],
            ];
        }

        // Get the remaining amount to check if the resource is fully refund
        $remaining_refundable_amount = $payment_method->getRefundableAmount($stored_resource['resource_id']);
        $refunded_amount = $payment_method->getRefundedAmount($stored_resource['resource_id']);

        // If there is no remain amount, and so we considere the resource fully refund, or $update_order_state is true
        // and a refund has been done
        // then we update the current order
        $reload = false;
        $refund_error = isset($refund['resource']->object) && 'error' == $refund['resource']->object;
        if ((!$remaining_refundable_amount || $update_order_state) && !$refund_error) {
            $state_addons = $refund['resource']->is_live ? '' : '_test';
            $new_state = (int) $this->plugin->getConfigurationClass()->getValue('order_state_refund' . $state_addons);
            $order = $this->plugin
                ->getOrder()
                ->get((int) $id_order);

            if ($this->plugin->getValidate()->validate('isLoadedObject', $order)) {
                $reload = $this->plugin
                    ->getOrderClass()
                    ->updateOrderState($order, $new_state);
            } else {
                $this->logger->addLog('PaymentAction::refundAction - The related Order object is not valid.', 'error');
            }
        }

        return [
            'result' => true,
            'data' => $this->renderRefundData($refunded_amount, $remaining_refundable_amount),
            'template' => $this->renderTemplate($id_order),
            'message' => $translations['success'],
            'modal' => $refund_error ? $this->renderModalTemplate() : '',
            'reload' => $reload,
        ];
    }

    /**
     * @description Process on the removal of a payment
     *
     * @param string $resource_id
     * @param bool $cancellable
     *
     * @return false
     */
    public function removeAction($resource_id = '', $cancellable = true)
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentAction::removeAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return false;
        }

        if (!is_bool($cancellable)) {
            $this->logger->addLog('PaymentAction::removeAction - Invalid argument, $cancellable must be a boolean.', 'error');

            return false;
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);
        if (empty($stored_resource)) {
            $this->logger->addLog('PaymentAction::removeAction - Can\'t get the stored payment resource.', 'error');

            return false;
        }
        $payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method']);
        $resource = $payment_method->retrieve($resource_id);

        if (!$resource['result']) {
            $this->logger->addLog('PaymentAction::removeAction - Can\'t retrieve the resource from given $resource_id.', 'error');

            return false;
        }

        // Check the resource is cancellable
        if (!$resource['resource']->failure && $cancellable) {
            $abort = $payment_method->abort($resource_id);
            if (!$abort['result']) {
                $this->logger->addLog('PaymentAction::removeAction - Can\'t abord the retrieved resource.', 'error');

                return false;
            }
        }

        // Remove the payment from the database
        return $this->plugin
            ->getPaymentRepository()
            ->deleteBy('resource_id', $resource_id);
    }

    /**
     * @description Process on the retrieve of a payment
     *
     * @param array $stored_resource
     * @param array $payment_tab
     *
     * @return array
     */
    public function retrieveAction($stored_resource = [], $payment_tab = [])
    {
        $this->setParameters();

        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->logger->addLog('PaymentAction::retrieveAction - Invalid argument, $payment_tab must be a non empty array.', 'error');

            return [];
        }

        if (!is_array($stored_resource) || empty($stored_resource)) {
            $this->logger->addLog('PaymentAction::retrieveAction - Invalid argument, $stored_resource must be a non empty array.', 'error');

            return [];
        }

        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method']);

        // Check if hash is valid then if not, return the createAction
        $is_live = !$this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('sandbox_mode');
        $payment_hash = $payment_method->getPaymentMethodHash($payment_tab, (bool) $is_live);
        if ($stored_resource['cart_hash'] != $payment_hash) {
            return $this->createAction($stored_resource['method'], $payment_tab);
        }

        return $payment_method->getReturnUrl();
    }

    /**
     * @description Render the refund modal template
     *
     * @return string
     */
    public function renderModalTemplate()
    {
        $this->setParameters();
        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($this->context->language->iso_code);
        $this->context->smarty->assign([
            'support_page_url' => $external_url['default'],
        ]);

        return $this->dependencies->configClass->fetchTemplate('/views/templates/admin/modal/refund.tpl');
    }

    /**
     * @description display payment errors
     *
     * @param array $errors
     *
     * @return string
     */
    public function renderPaymentErrors($errors = [])
    {
        $this->setParameters();

        if (!is_array($errors) || empty($errors)) {
            return '';
        }

        $formated = [];
        $with_msg_button = false;

        foreach ($errors as $error) {
            if (false !== strpos($error, 'oney_required_field')) {
                $this->context->smarty->assign(['is_popin_tpl' => true]);
                $formated[] = $this->plugin
                    ->getOneyAction()
                    ->renderRequiredFields($error);
            } else {
                $with_msg_button = true;
                $formated[] = [
                    'type' => 'string',
                    'value' => $error,
                ];
            }
        }

        $this->context->smarty->assign([
            'is_error_message' => true,
            'messages' => $formated,
            'with_msg_button' => $with_msg_button,
        ]);

        return $this->dependencies->configClass->fetchTemplate('_partials/messages.tpl');
    }

    /**
     * @description Render the refund data
     *
     * @param int $amount_refunded_payplug
     * @param int $amount_available
     *
     * @return string
     */
    public function renderRefundData($amount_refunded_payplug = 0, $amount_available = 0)
    {
        $this->setParameters();

        if (!is_int($amount_refunded_payplug)) {
            $this->logger->addLog('PaymentAction::renderRefundData - Invalid argument, $amount_refunded_payplug must be a valid integer.', 'error');

            return '';
        }

        if (!is_int($amount_available)) {
            $this->logger->addLog('PaymentAction::renderRefundData - Invalid argument, $amount_available must be a valid integer.', 'error');

            return '';
        }

        $this->context->smarty->assign([
            'amount_refunded_payplug' => $this->dependencies->getHelpers()['amount']->convertAmount($amount_refunded_payplug, true),
            'amount_available' => $this->dependencies->getHelpers()['amount']->convertAmount($amount_available, true),
        ]);

        return $this->dependencies->configClass->fetchTemplate('/views/templates/admin/order/refund_data.tpl');
    }

    /**
     * @description Render the order template
     *
     * @param int $id_order
     *
     * @return string
     */
    public function renderTemplate($id_order = 0)
    {
        $this->setParameters();

        if (!is_int($id_order) || !$id_order) {
            $this->logger->addLog('PaymentAction::renderTemplate - Invalid argument, $id_order must be a non null integer.', 'error');

            return '';
        }

        return $this->dependencies->hookClass->displayAdminOrderMain(['id_order' => $id_order]);
    }

    /**
     * @description Set needed object from dependencies
     */
    private function setParameters()
    {
        $this->plugin = $this->plugin ?: $this->dependencies
            ->getPlugin();
        $this->context = $this->context ?: $this->plugin
            ->getContext()->get();
        $this->logger = $this->logger ?: $this->plugin
            ->getLogger();
    }
}
