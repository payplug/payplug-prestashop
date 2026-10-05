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

use PayPlug\classes\DependenciesClass;
use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;

class UnifiedOrderAction
{
    use ServiceGetter;

    public $dependencies;

    /**
     * @description Store the given dependencies or build a new DependenciesClass
     *
     * @param mixed|null $dependencies
     */
    public function __construct($dependencies = null)
    {
        // Accepts an already-built DependenciesClass so callers that have one (any front
        // controller, OperationAction) can hand it over instead of paying for a second full
        // PluginInit object-graph construction; falls back to building its own so this class
        // stays a normal no-argument src/actions/*Action per this repo's own convention for
        // any caller that doesn't have one at hand (e.g. tests using newInstanceWithoutConstructor
        // can also just pass a mock here directly instead).
        $this->dependencies = $dependencies ?: new DependenciesClass();
    }

    /**
     * @description Create or reconcile the order for a cart from a Unified API payment outcome
     *
     * @param int $id_cart
     * @param string $operation_id
     * @param string $exec_code
     * @param string $outcome
     * @param int $amount
     * @param array{alias_id: string, brand: string}|null $pending_alias alias created by this operation, persisted on PAID
     * @param array{last4?: string|null, exp_month?: string|null, exp_year?: string|null} $card_details
     *
     * @return array{result: bool, redirect_url: string, persisted?: bool, outcome_applied?: bool}
     */
    public function createFromOutcome($id_cart, $operation_id, $exec_code, $outcome, $amount, $pending_alias = null, $card_details = [])
    {
        $plugin = $this->dependencies->getPlugin();
        $existing_order = $this->findExistingOrder($id_cart);

        if (null !== $existing_order) {
            // The order already exists: an abandoned one (error state) reconciled by a later
            // operation, or one whose paid state could not be applied (see
            // OperationAction::resyncPendingOrder()). This call may carry newer information for
            // the SAME operation_id, so it is persisted/applied here, not discarded.
            // OperationRepository::save() upserts by operation_id, and UpcOrderStateMutator's own
            // guards make a stale/out-of-order replay (e.g. a late FAILED after a PAID) safe.
            //
            // ExecCodeMapper, the only source of $outcome for every caller, produces three outcomes:
            //   - PAID: the order moves to the 'paid' bucket, and the alias this operation created,
            //     if any, is saved (persistAlias()).
            //   - THREE_DS_PENDING: every caller filters it out before calling this method, since
            //     a payment still pending never creates nor updates an order (PRE-3557 rule).
            //   - FAILED (anything else): the order moves to the 'error' bucket.
            // REFUNDED/AUTHORIZED/CAPTURE_REQUIRED are unreachable here. Back-office refunds made
            // through the Unified API are notified on notify.php with a payment-shaped body:
            // OperationAction::notifyAction() diverts them through the payplug_upc_refund lookup
            // before this path, and UpcOrderStateMutator's refunded-order guard neutralises a late
            // payment webhook on an already-refunded order.
            if (!$existing_order['owned_by_payplug']) {
                $this->logger()->error(
                    'UnifiedOrderAction::createFromOutcome - refusing to reconcile operation '
                    . $operation_id . ' onto order ' . $existing_order['id'] . ' for cart '
                    . $id_cart . ': that order was not created by this module'
                );

                return [
                    'result' => false,
                    'redirect_url' => $this->errorUrl(),
                    'persisted' => false,
                ];
            }

            return $this->persistAndApplyOutcome(
                $existing_order['id'],
                $operation_id,
                $exec_code,
                $outcome,
                $amount,
                $existing_order['secure_key'],
                $id_cart,
                $pending_alias,
                $card_details
            );
        }

        // PRE-3557 rule: an order is created only for a paid payment. A failed one never gets an
        // order, nor does a THREE_DS_PENDING one: the cardholder back from 3DS knows its result,
        // and the notification of the final outcome creates the order once paid.
        if (PaymentOutcome::PAID !== $outcome) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
                'persisted' => false,
            ];
        }

        $cart = $plugin->getCart()->get((int) $id_cart);
        if (!$cart || !(int) $cart->id) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
                'persisted' => false,
            ];
        }

        $secure_key = $this->resolveSecureKey((int) $id_cart);

        $is_live = !(bool) $plugin->getConfigurationClass()->getValue('sandbox_mode');
        $order_states = $plugin->getOrderClass()->getOrderStates($is_live);
        // Validated in the pending state, then moved to paid by persistAndApplyOutcome().
        $initial_state = isset($order_states['pending']) ? (int) $order_states['pending'] : 0;

        $order_adapter = $plugin->getOrder();
        $module = $plugin->getModule()->getInstanceByName($this->dependencies->name);

        try {
            $currency = $plugin->getCurrency()->getCurrency((int) $cart->id_currency);
            $currency_iso = is_object($currency) && isset($currency->iso_code) ? (string) $currency->iso_code : '';
            $amount_float = (float) $this->dependencies->getHelpers()['amount']->convertAmount((int) $amount, true, $currency_iso);
            $paid_state = isset($order_states['paid']) ? (int) $order_states['paid'] : 0;
            if ($paid_state && $this->differsFromCartTotal($cart, $currency_iso, (int) $amount)) {
                // As in the Retail flow: validated in the logable paid state, the order is switched
                // to PS_OS_ERROR by validateOrder() itself, which records the payment actually received.
                $initial_state = $paid_state;
            }
            $module->validateOrder(
                (int) $cart->id,
                $initial_state,
                $amount_float,
                $this->dependencies->name,
                null,
                ['transaction_id' => $operation_id],
                (int) $cart->id_currency,
                false,
                $secure_key
            );
        } catch (\Throwable $exception) {
            $this->logger()->error(
                'UnifiedOrderAction::createFromOutcome - validateOrder() failed for cart ' . $id_cart
                . ' (operation ' . $operation_id . '): ' . $exception->getMessage()
            );

            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
                'persisted' => false,
            ];
        }

        $new_order_id = (int) $order_adapter->getIdByCartId((int) $cart->id);
        if (!$new_order_id) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
                'persisted' => false,
            ];
        }

        return $this->persistAndApplyOutcome(
            $new_order_id,
            $operation_id,
            $exec_code,
            $outcome,
            $amount,
            $secure_key,
            $id_cart,
            $pending_alias,
            $card_details
        );
    }

    /**
     * @description Get the checkout error redirect URL
     */
    public function errorUrl()
    {
        return 'index.php?controller=order&step=3&has_error=1&modulename=' . $this->dependencies->name;
    }

    /**
     * @description Get the confirmation redirect for a cart whose order already exists
     *
     * Cheap, read-only equivalent of createFromOutcome()'s own existing-order check, exposed for
     * callers that want to skip an otherwise-unnecessary external API call/lock wait when the
     * order for this cart has already been created (e.g. by a concurrent webhook).
     *
     * @param int $id_cart
     *
     * @return array{result: bool, redirect_url: string}|null null when no order exists yet, or the
     *                       one that exists is abandoned and safe to reconcile over
     */
    public function existingOrderRedirect($id_cart)
    {
        $existing_order = $this->findExistingOrder($id_cart);

        if (null === $existing_order || $existing_order['abandoned']) {
            return null;
        }

        return [
            'result' => true,
            'redirect_url' => $this->confirmationUrl($id_cart, $existing_order['id'], $existing_order['secure_key']),
        ];
    }

    /**
     * @description Save the alias created by a paid operation, logging any failure
     *
     * The order is already validated and the customer charged: nothing here may change the
     * confirmation result. A refused save leaves no trace: the card is simply not kept.
     *
     * @param int $id_cart
     * @param array{alias_id: string, brand: string} $pending_alias
     * @param array{last4?: string|null, exp_month?: string|null, exp_year?: string|null} $card_details
     */
    public function persistAlias($id_cart, array $pending_alias, array $card_details)
    {
        $alias_id = isset($pending_alias['alias_id']) ? (string) $pending_alias['alias_id'] : '';

        try {
            $plugin = $this->dependencies->getPlugin();
            $cart = $plugin->getCart()->get((int) $id_cart);
            $id_customer = $cart && isset($cart->id_customer) ? (int) $cart->id_customer : 0;
            if ($id_customer <= 0) {
                $this->logger()->error(
                    'UnifiedOrderAction::persistAlias - failed to persist alias ' . $alias_id
                    . ' for cart ' . $id_cart . ': no customer'
                );

                return;
            }

            $currency = $plugin->getCurrency()->getCurrency((int) $cart->id_currency);
            $iso_code = $currency && isset($currency->iso_code) ? strtolower((string) $currency->iso_code) : '';
            $prestashop_adapter = $this->dependencies->loadAdapterPresta();
            $identifier = '' !== $iso_code && $prestashop_adapter
                ? (string) $prestashop_adapter->getHostedFieldsIdentifier($iso_code)
                : '';

            $saved = $this->getService('payplug.models.repositories.alias')->saveIfAbsent(
                $id_customer,
                $alias_id,
                $iso_code,
                $identifier,
                isset($pending_alias['brand']) ? (string) $pending_alias['brand'] : '',
                isset($card_details['last4']) ? $card_details['last4'] : null,
                isset($card_details['exp_month']) ? $card_details['exp_month'] : null,
                isset($card_details['exp_year']) ? $card_details['exp_year'] : null
            );

            if (!$saved) {
                $this->logger()->error(
                    'UnifiedOrderAction::persistAlias - failed to persist alias ' . $alias_id . ' for cart ' . $id_cart
                );
            }
        } catch (\Throwable $exception) {
            // Throwable: the customer is already charged, a TypeError must not fail the order.
            $this->logger()->error(
                'UnifiedOrderAction::persistAlias - failed to persist alias ' . $alias_id
                . ' for cart ' . $id_cart . ': ' . $exception->getMessage()
            );
        }
    }

    /**
     * @description Find the existing order for a cart and describe its ownership and state
     *
     * @param int $id_cart
     * @param mixed $plugin
     *
     * @return array{id: int, secure_key: string, owned_by_payplug: bool, abandoned: bool}|null
     *                   null when no order exists yet for this cart
     */
    private function findExistingOrder($id_cart)
    {
        $order_adapter = $this->dependencies->getPlugin()->getOrder();
        $existing_order_id = (int) $order_adapter->getIdByCartId((int) $id_cart);
        $existing_order = $order_adapter->get($existing_order_id);
        $order_exists = $this->dependencies->getValidators()['order']->isCreated($existing_order, (int) $id_cart);

        if (!$order_exists['result']) {
            return null;
        }

        return [
            'id' => $existing_order_id,
            'secure_key' => $this->resolveSecureKey((int) $id_cart, $existing_order),
            'owned_by_payplug' => isset($existing_order->module) && $existing_order->module === $this->dependencies->name,
            'abandoned' => $this->isAbandoned($existing_order),
        ];
    }

    /**
     * @description Check whether a PayPlug order is in the error (abandoned) state
     *
     * @param mixed $order
     *
     * @return bool
     */
    private function isAbandoned($order)
    {
        if (!is_object($order) || !isset($order->module) || $order->module !== $this->dependencies->name) {
            return false;
        }

        $plugin = $this->dependencies->getPlugin();
        $is_live = !(bool) $plugin->getConfigurationClass()->getValue('sandbox_mode');
        $order_states = $plugin->getOrderClass()->getOrderStates($is_live);
        $error_state = isset($order_states['error']) ? (int) $order_states['error'] : 0;
        $current_state = isset($order->current_state) ? (int) $order->current_state : 0;

        return $error_state > 0 && $current_state === $error_state;
    }

    /**
     * @description Persist the operation and apply its outcome to the order state
     *
     * @param int $order_id
     * @param string $operation_id
     * @param string $exec_code
     * @param string $outcome
     * @param int $amount
     * @param string $secure_key
     * @param int $id_cart
     * @param array{alias_id: string, brand: string}|null $pending_alias
     * @param array{last4?: string|null, exp_month?: string|null, exp_year?: string|null} $card_details
     *
     * @return array{result: bool, redirect_url: string, persisted: bool, outcome_applied: bool}
     *                       'outcome_applied' is false when the operation or the order state could not be
     *                       recorded: the order exists, but a retry is needed to bring it up to date
     */
    private function persistAndApplyOutcome($order_id, $operation_id, $exec_code, $outcome, $amount, $secure_key, $id_cart, $pending_alias = null, $card_details = [])
    {
        $module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $factory = $module->getService('payplug.utilities.service.unified_api_payment_service_factory');
        $outcome_applied = true;

        // An order whose total differs from the amount captured is never marked paid: it is put
        // in the error state ("Payment error"), as PrestaShop does when validating such an order.
        $state_outcome = $outcome;
        if (PaymentOutcome::PAID === $outcome) {
            $orders_total = $this->ordersTotal($order_id, $factory);
            if (null !== $orders_total && $orders_total !== (int) $amount) {
                $factory->createLogger()->error(
                    'UnifiedOrderAction::persistAndApplyOutcome - order ' . $order_id . ' total ' . $orders_total
                    . ' differs from the amount ' . (int) $amount . ' paid by operation ' . $operation_id
                    . ': set to payment error instead of paid, manual review needed'
                );
                $state_outcome = PaymentOutcome::FAILED;
            }
        }

        try {
            $factory->createPaymentRepository()->save(new OperationData(
                $operation_id,
                $exec_code,
                $outcome,
                (int) $amount,
                (string) $order_id
            ));
            $factory->createOrderStateMutator()->apply((string) $order_id, $state_outcome);
        } catch (\Throwable $exception) {
            // Throwable: the order exists and the customer may be charged, a TypeError must be reported too.
            $factory->createLogger()->error(
                'UnifiedOrderAction::persistAndApplyOutcome - order ' . $order_id
                . ' (operation ' . $operation_id . ') exists but persisting/applying its '
                . 'payment state failed: ' . $exception->getMessage()
            );
            $outcome_applied = false;
        }

        if (PaymentOutcome::FAILED === $outcome) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
                // Whether or not save() went through, this method is the only place that persists
                // this operation_id with the real order_id: callers (notifyAction()) must never
                // re-save it with another order_id.
                'persisted' => true,
                'outcome_applied' => $outcome_applied,
            ];
        }

        if (PaymentOutcome::PAID === $outcome && is_array($pending_alias)) {
            $this->persistAlias($id_cart, $pending_alias, is_array($card_details) ? $card_details : []);
        }

        // The order exists: the customer gets its confirmation even when its state is not up to date.
        return [
            'result' => true,
            'redirect_url' => $this->confirmationUrl($id_cart, $order_id, $secure_key),
            'persisted' => true,
            'outcome_applied' => $outcome_applied,
        ];
    }

    /**
     * @description Tell whether the cart total differs from the amount paid, in minor units
     *
     * @param mixed $cart
     * @param string $currency_iso
     * @param int $amount
     *
     * @return bool false when the total cannot be computed: persistAndApplyOutcome() still checks the order
     */
    private function differsFromCartTotal($cart, $currency_iso, $amount)
    {
        try {
            $cart_total = (int) $this->dependencies->getHelpers()['amount']->convertAmount(
                (float) $cart->getOrderTotal(true, \Cart::BOTH),
                false,
                $currency_iso
            );
        } catch (\Exception $exception) {
            return false;
        }

        return $cart_total !== $amount;
    }

    /**
     * @description Get the total of all the orders created from the order's cart, in minor units
     *
     * @param int $order_id
     * @param mixed $factory
     *
     * @return int|null null when it cannot be computed (logged)
     */
    private function ordersTotal($order_id, $factory)
    {
        try {
            $plugin = $this->dependencies->getPlugin();
            $order = $plugin->getOrder()->get((int) $order_id);
            if (!is_object($order) || !isset($order->id_currency)) {
                return null;
            }

            $currency = $plugin->getCurrency()->getCurrency((int) $order->id_currency);
            $currency_iso = is_object($currency) && isset($currency->iso_code) ? (string) $currency->iso_code : '';

            return (int) $this->dependencies->getHelpers()['amount']->convertAmount(
                (float) $order->getOrdersTotalPaid(),
                false,
                $currency_iso
            );
        } catch (\Throwable $exception) {
            $factory->createLogger()->error(
                'UnifiedOrderAction::persistAndApplyOutcome - total of order ' . $order_id
                . ' could not be compared with the amount paid: ' . $exception->getMessage()
            );

            return null;
        }
    }

    /**
     * @description Get the UPC logger from the unified API payment service factory
     */
    private function logger()
    {
        $module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);

        return $module->getService('payplug.utilities.service.unified_api_payment_service_factory')->createLogger();
    }

    /**
     * @description Build the order confirmation URL for a cart and order
     *
     * @param mixed $id_cart
     * @param mixed $order_id
     * @param mixed $secure_key
     */
    private function confirmationUrl($id_cart, $order_id = 0, $secure_key = '')
    {
        $plugin = $this->dependencies->getPlugin();
        $context_adapter = $plugin->getContext();
        $context = is_object($context_adapter) && method_exists($context_adapter, 'get')
            ? $context_adapter->get()
            : $context_adapter;

        if (is_object($context)
            && isset($context->link)
            && is_object($context->link)
            && method_exists($context->link, 'getPageLink')) {
            $module = $plugin->getModule()->getInstanceByName($this->dependencies->name);
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

    /**
     * @description Resolve the secure key from the order, cart or customer
     *
     * @param mixed $id_cart
     * @param mixed|null $order
     */
    private function resolveSecureKey($id_cart, $order = null)
    {
        if (is_object($order) && isset($order->secure_key) && $order->secure_key) {
            return $order->secure_key;
        }

        $plugin = $this->dependencies->getPlugin();
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
