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

use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;

class UnifiedOrderCreator
{
    /**
     * @param object $dependencies_class
     * @param int $id_cart
     * @param string $operation_id
     * @param string $exec_code
     * @param string $outcome
     * @param int $amount
     *
     * @return array{result: bool, redirect_url: string}
     */
    public static function createFromOutcome($dependencies_class, $id_cart, $operation_id, $exec_code, $outcome, $amount)
    {
        $plugin = $dependencies_class->getPlugin();
        $order_adapter = $plugin->getOrder();
        $existing_order_id = (int) $order_adapter->getIdByCartId((int) $id_cart);
        $existing_order = $order_adapter->get($existing_order_id);
        $order_exists = $dependencies_class->getValidators()['order']->isCreated($existing_order, (int) $id_cart);
        $secure_key = self::resolveSecureKey($plugin, (int) $id_cart, $existing_order);

        if ($order_exists['result']) {
            return [
                'result' => true,
                'redirect_url' => self::confirmationUrl($dependencies_class, $id_cart, $existing_order_id, $secure_key),
            ];
        }

        if (PaymentOutcome::FAILED === $outcome) {
            return [
                'result' => false,
                'redirect_url' => self::errorUrl($dependencies_class),
            ];
        }

        $cart = $plugin->getCart()->get((int) $id_cart);
        if (!$cart || !(int) $cart->id) {
            return [
                'result' => false,
                'redirect_url' => self::errorUrl($dependencies_class),
            ];
        }

        $secure_key = self::resolveSecureKey($plugin, (int) $id_cart);

        $is_live = !(bool) $plugin->getConfigurationClass()->getValue('sandbox_mode');
        $order_states = $plugin->getOrderClass()->getOrderStates($is_live);
        $initial_state = isset($order_states['pending']) ? (int) $order_states['pending'] : 0;

        $module = $plugin->getModule()->getInstanceByName($dependencies_class->name);
        $amount_float = (float) $dependencies_class->getHelpers()['amount']->convertAmount((int) $amount, true);

        try {
            $module->validateOrder(
                (int) $cart->id,
                $initial_state,
                $amount_float,
                $dependencies_class->name,
                null,
                ['transaction_id' => $operation_id],
                (int) $cart->id_currency,
                false,
                $secure_key
            );
        } catch (\Exception $exception) {
            return [
                'result' => false,
                'redirect_url' => self::errorUrl($dependencies_class),
            ];
        }

        $new_order_id = (int) $order_adapter->getIdByCartId((int) $cart->id);
        if (!$new_order_id) {
            return [
                'result' => false,
                'redirect_url' => self::errorUrl($dependencies_class),
            ];
        }

        try {
            $factory = $module->getService('payplug.utilities.service.unified_api_payment_service_factory');
            $factory->createPaymentRepository()->save(new OperationData(
                $operation_id,
                $exec_code,
                $outcome,
                (int) $amount,
                (string) $new_order_id
            ));
            $factory->createOrderStateMutator()->apply((string) $new_order_id, $outcome);
        } catch (\Exception $exception) {
            return [
                'result' => false,
                'redirect_url' => self::errorUrl($dependencies_class),
            ];
        }

        return [
            'result' => true,
            'redirect_url' => self::confirmationUrl($dependencies_class, $id_cart, $new_order_id, $secure_key),
        ];
    }

    public static function errorUrl($dependencies_class)
    {
        return 'index.php?controller=order&step=3&has_error=1&modulename=' . $dependencies_class->name;
    }

    private static function confirmationUrl($dependencies_class, $id_cart, $order_id = 0, $secure_key = '')
    {
        $plugin = $dependencies_class->getPlugin();
        $context_adapter = $plugin->getContext();
        $context = is_object($context_adapter) && method_exists($context_adapter, 'get')
            ? $context_adapter->get()
            : $context_adapter;

        if (is_object($context)
            && isset($context->link)
            && is_object($context->link)
            && method_exists($context->link, 'getPageLink')) {
            $module = $plugin->getModule()->getInstanceByName($dependencies_class->name);
            $module_id = isset($module->id) ? (int) $module->id : 0;

            return $context->link->getPageLink('order-confirmation', true, null, [
                'id_cart' => (int) $id_cart,
                'id_module' => $module_id,
                'id_order' => (int) $order_id,
                'key' => $secure_key,
            ]);
        }

        return 'index.php?controller=order-confirmation&id_cart=' . (int) $id_cart
            . '&id_order=' . (int) $order_id
            . '&key=' . rawurlencode((string) $secure_key);
    }

    private static function resolveSecureKey($plugin, $id_cart, $order = null)
    {
        if (is_object($order) && isset($order->secure_key) && $order->secure_key) {
            return $order->secure_key;
        }

        $cart = $plugin->getCart()->get((int) $id_cart);
        if ($cart && isset($cart->secure_key) && $cart->secure_key) {
            return $cart->secure_key;
        }

        if ($cart && isset($cart->id_customer)) {
            $customer = $plugin->getCustomer()->get((int) $cart->id_customer);

            if ($customer && isset($customer->secure_key) && $customer->secure_key) {
                return $customer->secure_key;
            }
        }

        return '';
    }
}
