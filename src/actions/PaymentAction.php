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
        'giropay',
        'ideal',
        'installment',
        'mybank',
        'one_click',
        'oney',
        'satispay',
        'sofort',
        'standard',
    ];
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

        $abort = $this->dependencies->apiClass->abortInstallment($resource_id);
        if (!$abort['result']) {
            $sandbox = (bool) $this->plugin
                ->getConfigurationClass()
                ->getValue('sandbox_mode');
            $live_api_key = $this->plugin
                ->getConfigurationClass()
                ->getValue('live_api_key');
            $test_api_key = $this->plugin
                ->getConfigurationClass()
                ->getValue('test_api_key');

            if ($sandbox) {
                $this->dependencies->apiClass->setSecretKey($live_api_key);
                $abort = $this->dependencies->apiClass->abortInstallment($resource_id);
                $this->dependencies->apiClass->setSecretKey($test_api_key);
            } elseif (!$sandbox) {
                $this->dependencies->apiClass->setSecretKey($test_api_key);
                $abort = $this->dependencies->apiClass->abortInstallment($resource_id);
                $this->dependencies->apiClass->setSecretKey($live_api_key);
            }
        }
        if (!$abort['result']) {
            $this->logger->addLog('PaymentAction::abortAction - Can not abort the payment.', 'error');

            return [
                'result' => false,
                'message' => 'Can not abort the payment.',
            ];
        }

        $retrieve = $this->dependencies->apiClass->retrieveInstallment($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentAction::abortAction - Can not retrieve the aborted payment.', 'error');

            return [
                'result' => false,
                'message' => 'Can not retrieve the aborted payment.',
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

        $current_state = (int) $order->getCurrentState();
        if ($current_state && $current_state != $new_state) {
            $order_history = $this->plugin
                ->getOrderHistory()
                ->get();
            $order_history->id_order = (int) $order->id;
            $order_history->changeIdOrderState($new_state, (int) $order->id, true);
            $order_history->addWithemail();
        }

        $step_to_update = [];
        foreach ($installment->schedule as $key => $schedule) {
            $pay_id = '';
            if (count($schedule->payment_ids) > 0) {
                $pay_id = $schedule->payment_ids[0];
                $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
                if (!$payment['result']) {
                    $this->logger->addLog('PaymentAction::abortAction - Unable to retrieve the schedule payment', 'error');

                    return [
                        'result' => false,
                        'message' => 'Unable to retrieve the schedule payment',
                    ];
                }
                $payment = $payment['resource'];
                $status = $this->dependencies->paymentClass->getPaymentStatusByPayment($payment);
            } else {
                if (1 == (int) $installment->is_active) {
                    $status = 6; // ongoing
                } else {
                    $status = 7; // cancelled
                }
            }
            $step = ($key + 1) . '/' . count($installment->schedule);
            $step_to_update[$step] = [
                'pay_id' => $pay_id,
                'status' => (int) $status,
            ];
        }

        $resource = $this->plugin
            ->getPaymentRepository()
            ->getByResourceId($installment->id);
        $schedules = json_decode((string) $resource['schedules'], true);
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
                ->updateByResourceId($installment->id, [
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

        $capture = $this->dependencies->apiClass->capturePayment($resource_id);
        if (!$capture['result']) {
            $sandbox = (bool) $this->plugin
                ->getConfigurationClass()
                ->getValue('sandbox_mode');
            $live_api_key = $this->plugin
                ->getConfigurationClass()
                ->getValue('live_api_key');
            $test_api_key = $this->plugin
                ->getConfigurationClass()
                ->getValue('test_api_key');

            if ($sandbox) {
                $this->dependencies->apiClass->setSecretKey($live_api_key);
                $capture = $this->dependencies->apiClass->capturePayment($resource_id);
                $this->dependencies->apiClass->setSecretKey($test_api_key);
            } elseif (!$sandbox) {
                $this->dependencies->apiClass->setSecretKey($test_api_key);
                $capture = $this->dependencies->apiClass->capturePayment($resource_id);
                $this->dependencies->apiClass->setSecretKey($live_api_key);
            }
        }
        if (!$capture['result']) {
            $this->logger->addLog('PaymentAction::captureAction - Can not capture the payment.', 'error');

            return [
                'result' => false,
                'message' => 'Can not capture the payment.',
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

        if (!$this->dependencies->cartClass->createLockFromCartId((int) $order->id_cart)) {
            $this->logger->addLog('PaymentAction::captureAction - An error occured on lock creation', 'error');

            return [
                'result' => false,
                'message' => 'An error occured on lock creation',
            ];
        }

        $current_state = (int) $order->getCurrentState();
        if ($current_state && $current_state != $new_state) {
            $order_history = $this->plugin
                ->getOrderHistory()
                ->get();
            $order_history->id_order = (int) $order->id;
            $order_history->changeIdOrderState($new_state, (int) $order->id, true);
            $order_history->addWithemail();
        }

        $delete_lock = $this->plugin
            ->getLockRepository()
            ->deleteLock((int) $order->id_cart);
        if (!$delete_lock) {
            $this->logger->addLog('PaymentAction::captureAction - Lock cannot be deleted.', 'error');
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
            ->getByCart((int) $cart_id);
        if (!empty($resource)) {
            $payment_method = $this->plugin
                ->getPaymentMethodClass()
                ->getPaymentMethod($resource['method']);
            $removed = $this->removeAction($resource['resource_id'], $payment_method->cancellable);
            if (!$removed) {
                $this->logger->addLog('PaymentAction::createAction - Stored resource can not be remove.', 'error');

                return [];
            }
        }

        // Create the payment from given payment_tab
        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($method);

        $resource = $payment_method->saveResource($payment_tab);
        if (!$resource['result']) {
            $this->logger->addLog('PaymentAction::createAction - Resource can not be created from given tab.', 'error');

            return [];
        }

        // Generate the hash and create payment in database
        $payment_hash = $payment_method->getPaymentMethodHash();
        $parameters = [
            'resource_id' => $resource['resource']->id,
            'method' => $method,
            'id_cart' => (int) $cart_id,
            'cart_hash' => $payment_hash,
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        $save_hash = $this->plugin
            ->getPaymentRepository()
            ->createPayment($parameters);
        if (!$save_hash) {
            $this->logger->addLog('PaymentAction::createAction - Payment method hash can not be generated.', 'error');

            return [];
        }

        if ('applepay' == $method) {
            return $resource;
        }

        return $payment_method->getReturnUrl();
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
            ->getByCart((int) $cart_id);

        $should_create_resource = $force_resource_creation
            || empty($stored_resource)
            || !$payment_method->isValidResource();

        if ($should_create_resource) {
            return $this->createAction($method, $payment_tab);
        }

        return $this->retrieveAction($stored_resource, $payment_tab);
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

        $is_installment = false !== strpos($resource_id, 'inst_');
        $resource = $is_installment
            ? $this->dependencies->apiClass->retrieveInstallment($resource_id)
            : $this->dependencies->apiClass->retrievePayment($resource_id);

        if (!$resource['result']) {
            $this->logger->addLog('PaymentAction::removeAction - Can not retrieve the resource from given $resource_id.', 'error');

            return false;
        }

        // Check the resource is cancellable
        if (!$resource['resource']->failure && $cancellable) {
            $abort = $is_installment
                ? $this->dependencies->apiClass->abortInstallment($resource_id)
                : $this->dependencies->apiClass->abortPayment($resource_id);
            if (!$abort['result']) {
                $this->logger->addLog('PaymentAction::removeAction - Can not abord the retrieved resource.', 'error');

                return false;
            }
        }

        // Remove the payment from the database
        return $this->plugin
            ->getPaymentRepository()
            ->removeByResourceId($resource_id);
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
        $payment_hash = $payment_method->getPaymentMethodHash();
        if ($stored_resource['cart_hash'] != $payment_hash) {
            return $this->createAction($stored_resource['method'], $payment_tab);
        }

        return $payment_method->getReturnUrl();
    }

    private function setParameters()
    {
        $this->plugin = $this->plugin ?: $this->dependencies
            ->getPlugin();
        $this->logger = $this->logger ?: $this->plugin
            ->getLogger();
    }
}
