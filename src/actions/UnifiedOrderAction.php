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
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;

class UnifiedOrderAction
{
    public $dependencies;

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
     * @param int $id_cart
     * @param string $operation_id
     * @param string $exec_code
     * @param string $outcome
     * @param int $amount
     *
     * @return array{result: bool, redirect_url: string}
     */
    public function createFromOutcome($id_cart, $operation_id, $exec_code, $outcome, $amount)
    {
        $plugin = $this->dependencies->getPlugin();
        $existing_order = $this->findExistingOrder($id_cart);

        if (null !== $existing_order) {
            // The order already exists (created by an earlier returnAction()/createAction()
            // reconciliation call for this cart, possibly still in a THREE_DS_PENDING/pending
            // state). This call may carry newer information for the SAME operation_id - most
            // notably notifyAction() delivering the real, final outcome after a synchronous
            // return created the order prematurely - so it must still be persisted/applied here,
            // not silently discarded. OperationRepository::save() upserts by operation_id, and
            // UpcOrderStateMutator::apply()'s own terminal-state guard makes re-applying a stale/
            // out-of-order replay (e.g. a late FAILED after a PAID already landed) safe.
            //
            // The three outcomes ExecCodeMapper can ever hand this method (it's the only source
            // of $outcome for every caller - createAction(), returnAction(), notifyAction()):
            //   - SUCCESS_EXEC_CODE ('0000') -> PaymentOutcome::PAID: the order is updated (moved
            //     to the 'paid' bucket by UpcOrderStateMutator).
            //   - PENDING_THREE_DS_EXEC_CODE ('0001') -> PaymentOutcome::THREE_DS_PENDING: never
            //     reaches THIS branch specifically (an existing order). notifyAction() and the
            //     createAction() reconciliation path (reconcilePendingOperation()) both
            //     special-case it as a strict no-op before ever calling createFromOutcome(). And
            //     while returnAction() does NOT filter it - it computes $outcome from its own
            //     synchronous getOperation() call with no THREE_DS_PENDING guard at all - it only
            //     ever reaches createFromOutcome() when existingOrderRedirect() found no order yet
            //     (checked first, returning immediately otherwise), so any THREE_DS_PENDING it
            //     passes in always lands in the OTHER branch below (no existing order:
            //     validateOrder() creates a fresh order in the 'pending' state; this is the
            //     intended behavior - the later webhook notification is what confirms/finalizes
            //     it, not this synchronous return), never in this one.
            //   - anything else -> PaymentOutcome::FAILED: the order is updated too (moved to the
            //     'error'/abandoned bucket).
            // PaymentOutcome::REFUNDED/AUTHORIZED/CAPTURE_REQUIRED are deliberately unreachable
            // from this call chain: ExecCodeMapper::toPaymentOutcome() (the only $outcome source
            // here) never produces them. Refund notifications in particular are sent by the
            // classic/GM API, not the Unified API's WebhookNotificationHelper this module's
            // notifyAction() parses - they must not be handled by this reconciliation path, and
            // today structurally can't be (a refund payload wouldn't parse as a
            // WebhookNotificationHelper OperationData in the first place).
            return $this->persistAndApplyOutcome(
                $existing_order['id'],
                $operation_id,
                $exec_code,
                $outcome,
                $amount,
                $existing_order['secure_key'],
                $id_cart
            );
        }

        if (PaymentOutcome::FAILED === $outcome) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
            ];
        }

        $cart = $plugin->getCart()->get((int) $id_cart);
        if (!$cart || !(int) $cart->id) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
            ];
        }

        $secure_key = $this->resolveSecureKey((int) $id_cart);

        $is_live = !(bool) $plugin->getConfigurationClass()->getValue('sandbox_mode');
        $order_states = $plugin->getOrderClass()->getOrderStates($is_live);
        $initial_state = isset($order_states['pending']) ? (int) $order_states['pending'] : 0;

        $order_adapter = $plugin->getOrder();
        $module = $plugin->getModule()->getInstanceByName($this->dependencies->name);
        $amount_float = (float) $this->dependencies->getHelpers()['amount']->convertAmount((int) $amount, true);

        try {
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
        } catch (\Exception $exception) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
            ];
        }

        $new_order_id = (int) $order_adapter->getIdByCartId((int) $cart->id);
        if (!$new_order_id) {
            return [
                'result' => false,
                'redirect_url' => $this->errorUrl(),
            ];
        }

        return $this->persistAndApplyOutcome($new_order_id, $operation_id, $exec_code, $outcome, $amount, $secure_key, $id_cart);
    }

    public function errorUrl()
    {
        return 'index.php?controller=order&step=3&has_error=1&modulename=' . $this->dependencies->name;
    }

    /**
     * Cheap, read-only equivalent of createFromOutcome()'s own existing-order check, exposed for
     * callers that want to skip an otherwise-unnecessary external API call/lock wait when the
     * order for this cart has already been created (e.g. by a concurrent webhook).
     *
     * @param int $id_cart
     *
     * @return array{result: bool, redirect_url: string}|null null when no order exists yet
     */
    public function existingOrderRedirect($id_cart)
    {
        $existing_order = $this->findExistingOrder($id_cart);

        if (null === $existing_order) {
            return null;
        }

        return [
            'result' => true,
            'redirect_url' => $this->confirmationUrl($id_cart, $existing_order['id'], $existing_order['secure_key']),
        ];
    }

    /**
     * @param int $id_cart
     * @param mixed $plugin
     *
     * @return array{id: int, secure_key: string}|null null when no order exists yet for this cart
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
        ];
    }

    /**
     * Shared by both the new-order path (after validateOrder() succeeds) and the existing-order
     * reconciliation path: persists the OperationData row and applies the resulting order-state
     * transition, then redirects to the confirmation page - EXCEPT when both of these are true:
     * the outcome being persisted is FAILED, AND persisting/applying it itself throws. In that
     * specific case there is no successful charge to protect the customer's view of (unlike the
     * PAID/other-outcome case, where the failure is logged loudly for ops to reconcile but the
     * order genuinely exists and was charged, so it must never send them to the error page) - the
     * order's state may now be stuck out of sync with the real, final FAILED outcome, so this
     * reports failure rather than silently confirming a payment that didn't happen.
     *
     * @param int $order_id
     * @param string $operation_id
     * @param string $exec_code
     * @param string $outcome
     * @param int $amount
     * @param string $secure_key
     * @param int $id_cart
     *
     * @return array{result: bool, redirect_url: string}
     */
    private function persistAndApplyOutcome($order_id, $operation_id, $exec_code, $outcome, $amount, $secure_key, $id_cart)
    {
        $module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $factory = $module->getService('payplug.utilities.service.unified_api_payment_service_factory');

        try {
            $factory->createPaymentRepository()->save(new OperationData(
                $operation_id,
                $exec_code,
                $outcome,
                (int) $amount,
                (string) $order_id
            ));
            $factory->createOrderStateMutator()->apply((string) $order_id, $outcome);
        } catch (\Exception $exception) {
            $factory->createLogger()->error(
                'UnifiedOrderAction::persistAndApplyOutcome - order ' . $order_id
                . ' (operation ' . $operation_id . ') exists but persisting/applying its '
                . 'payment state failed: ' . $exception->getMessage()
            );

            if (PaymentOutcome::FAILED === $outcome) {
                return [
                    'result' => false,
                    'redirect_url' => $this->errorUrl(),
                ];
            }
        }

        return [
            'result' => true,
            'redirect_url' => $this->confirmationUrl($id_cart, $order_id, $secure_key),
        ];
    }

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
