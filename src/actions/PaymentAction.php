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
    private $dependencies;
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

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
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
        if (!is_string($method) || !$method) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::dispatchAction - Invalid argument, $method must be a string.', 'error');

            return [];
        }

        if (!in_array($method, $this->available_payment)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::dispatchAction - Invalid argument, $method given is not expected.', 'error');

            return [];
        }

        $payment_methods = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('payment_methods');
        $payment_methods = json_decode($payment_methods, true);
        if (!$force) {
            switch ($method) {
                case 'one_click':
                    return [
                        'return_url' => 'index.php?controller=order&step=3&embedded=1'
                            . '&pc=' . $this->dependencies
                                ->getPlugin()
                                ->getTools()
                                ->tool('getValue', 'pc')
                            . '&def=' . (int) $payment_methods['deferred']
                            . '&modulename=' . $this->dependencies->name,
                    ];
                case 'amex':
                case 'installment':
                case 'standard':
                    if ('redirect' != (string) $this->dependencies
                        ->getPlugin()
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
        $payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($method);
        $payment_tab = $payment_method->getPaymentTab();
        if (empty($payment_tab)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::dispatchAction - Cannot generate payment tab.', 'error');

            return [];
        }

        // Check if payment already exists or if resource creation is forced
        $force_resource_creation = $payment_method->force_resource;
        $cart_id = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->cart->id;
        $stored_resource = $this->dependencies
            ->getPlugin()
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
     * @description Process on the creation of a payment
     *
     * @param string $method
     * @param array $payment_tab
     *
     * @return array
     */
    public function createAction($method = '', $payment_tab = [])
    {
        if (!is_string($method) || !$method) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::createAction - Invalid argument, $method must be a non empty string.', 'error');

            return [];
        }

        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::createAction - Invalid argument, $payment_tab must be a non empty array.', 'error');

            return [];
        }

        $cart_id = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->cart->id;

        // If a payment exists, we try to cancel it and remove from database.
        $resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $cart_id);
        if (!empty($resource)) {
            $payment_method = $this->dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentMethod($resource['method']);
            $removed = $this->removeAction($resource['resource_id'], $payment_method->cancellable);
            if (!$removed) {
                $this->dependencies
                    ->getPlugin()
                    ->getLogger()
                    ->addLog('PaymentAction::createAction - Stored resource can not be remove.', 'error');

                return [];
            }
        }

        // Create the payment from given payment_tab
        $payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($method);

        $resource = $payment_method->saveResource($payment_tab);
        if (!$resource['result']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::createAction - Resource can not be created from given tab.', 'error');

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
        $save_hash = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->createPayment($parameters);
        if (!$save_hash) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::createAction - Payment method hash can not be generated.', 'error');

            return [];
        }

        if ('applepay' == $method) {
            return $resource;
        }

        return $payment_method->getReturnUrl();
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
        if (!is_string($resource_id) || !$resource_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::removeAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return false;
        }

        if (!is_bool($cancellable)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::removeAction - Invalid argument, $cancellable must be a boolean.', 'error');

            return false;
        }

        $is_installment = false !== strpos($resource_id, 'inst_');
        $resource = $is_installment
            ? $this->dependencies->apiClass->retrieveInstallment($resource_id)
            : $this->dependencies->apiClass->retrievePayment($resource_id);

        if (!$resource['result']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::removeAction - Can not retrieve the resource from given $resource_id.', 'error');

            return false;
        }

        // Check the resource is cancellable
        if (!$resource['resource']->failure && $cancellable) {
            $abort = $is_installment
                ? $this->dependencies->apiClass->abortInstallment($resource_id)
                : $this->dependencies->apiClass->abortPayment($resource_id);
            if (!$abort['result']) {
                $this->dependencies
                    ->getPlugin()
                    ->getLogger()
                    ->addLog('PaymentAction::removeAction - Can not abord the retrieved resource.', 'error');

                return false;
            }
        }

        // Remove the payment from the database
        return $this->dependencies
            ->getPlugin()
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
        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::retrieveAction - Invalid argument, $payment_tab must be a non empty array.', 'error');

            return [];
        }

        if (!is_array($stored_resource) || empty($stored_resource)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentAction::retrieveAction - Invalid argument, $stored_resource must be a non empty array.', 'error');

            return [];
        }

        $payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method']);

        // Check if hash is valid then if not, return the createAction
        $payment_hash = $payment_method->getPaymentMethodHash();
        if ($stored_resource['cart_hash'] != $payment_hash) {
            return $this->createAction($stored_resource['method'], $payment_tab);
        }

        return $payment_method->getReturnUrl();
    }
}
