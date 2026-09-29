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
use PayPlug\src\models\classes\UpcRefundExecCode;
use PayPlug\src\models\repositories\UpcRefundRepository;
use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\Exceptions\ApiException;
use PayplugUnifiedCore\Exceptions\InvalidRefundRequestException;
use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;
use PayplugUnifiedCore\Exceptions\RefundAmountException;

class UnifiedRefundAction
{
    use ServiceGetter;

    /**
     * Held for the whole refund, until its row carries the refund's own operation id: the
     * notification path waits on it before giving up on an unmatched refund notification.
     */
    public const LOCK_KEY_PREFIX = 'uhf_refund:';

    private const FACTORY_SERVICE = 'payplug.utilities.service.unified_api_payment_service_factory';
    private const REFUND_REPOSITORY_SERVICE = 'payplug.models.repositories.upc_refund';

    /**
     * Placeholder refund_operation_id of the row recorded before createRefund() is called,
     * replaced by the refund's own operation id once the API answers.
     */
    private const INTENT_ID_PREFIX = 'intent:';

    /**
     * Above the worst case of token fetch + refund POST + 401 token refresh + refund POST,
     * with CurlHttpClient's 15 s timeout per call (about 60 s).
     */
    private const REFUND_LOCK_TTL = 90;

    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Refund, fully or partially, an order paid with Unified Hosted Fields
     *
     * No idempotency key exists on the Unified API refund endpoint, so the refund is recorded as
     * pending before the call: when its outcome is unknown (timeout, 5xx, undocumented execCode),
     * the row stays pending and keeps counting as refunded, and the merchant is asked to check the
     * PayPlug portal - a retry is blocked instead of refunding twice. The lock guards concurrent
     * requests (double click, two BO tabs).
     *
     * @param int $id_order
     * @param int $amount in cents
     *
     * @return array
     */
    public function refundAction($id_order = 0, $amount = 0)
    {
        $plugin = $this->dependencies->getPlugin();
        $translations = $plugin->getTranslationClass()->getRefundTranslations();
        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();

        if (!is_int($amount) || $amount <= 0) {
            $logger->error('UnifiedRefundAction::refundAction - Invalid argument, $amount must be a positive integer.');

            return $this->errorResult($translations['error']['format']);
        }

        if (!is_int($id_order) || $id_order <= 0) {
            $logger->error('UnifiedRefundAction::refundAction - Invalid argument, $id_order must be a positive integer.');

            return $this->errorResult($translations['error']['default']);
        }

        $order = $plugin->getOrder()->get($id_order);
        if (!$plugin->getValidate()->validate('isLoadedObject', $order) || $order->module !== $this->dependencies->name) {
            $logger->error('UnifiedRefundAction::refundAction - Order ' . $id_order . ' is not a valid order of this module.');

            return $this->errorResult($translations['error']['default']);
        }

        $payment_repository = $factory->createPaymentRepository();
        $payment_operation = $payment_repository->getPaidByOrderId((string) $order->id);
        if (null === $payment_operation) {
            $logger->error('UnifiedRefundAction::refundAction - No paid UHF operation found for order ' . $order->id);

            return $this->errorResult($translations['error']['default']);
        }

        // The refund endpoint is keyed by the payment's own id, bound to the operation by
        // OperationAction::createAction() - the operation id is rejected with a 400
        // ("The reference transaction has not been found.").
        $payment_id = $payment_repository->getPaymentIdByOperationId($payment_operation->operationId);
        if (null === $payment_id) {
            $logger->error('UnifiedRefundAction::refundAction - Order ' . $order->id
                . ' cannot be refunded: no Unified API payment id recorded for operation ' . $payment_operation->operationId);

            return $this->errorResult($translations['error']['default']);
        }

        $currency = $plugin->getCurrency()->get((int) $order->id_currency);
        $currency_iso = is_object($currency) && isset($currency->iso_code) ? strtoupper((string) $currency->iso_code) : '';
        $prestashop_adapter = $this->dependencies->loadAdapterPresta();
        $account_id = '' !== $currency_iso && $prestashop_adapter
            ? (string) $prestashop_adapter->getHostedFieldsIdentifier($currency_iso)
            : '';

        // Without it the account id goes out empty, the API answers a generic 4xx, and a one-line
        // config problem becomes indistinguishable from any other API failure.
        if ('' === $account_id) {
            $logger->error('UnifiedRefundAction::refundAction - UHF refund misconfigured for order ' . $order->id
                . ': no hosted fields identifier configured for currency "' . $currency_iso . '".');

            return $this->errorResult($translations['error']['default']);
        }

        $refund_repository = $this->getService(self::REFUND_REPOSITORY_SERVICE);
        $lock = $factory->createLock();
        $lock_key = self::LOCK_KEY_PREFIX . (int) $order->id;

        if (!$lock->acquire($lock_key, self::REFUND_LOCK_TTL)) {
            $logger->error('UnifiedRefundAction::refundAction - Another refund is already in progress for order ' . $order->id);

            return $this->errorResult($translations['error']['default']);
        }

        try {
            // Read once the lock is held, so two concurrent refunds can't both see the same balance.
            $available = (int) $payment_operation->amount - (int) $refund_repository->getRefundedAmount((string) $order->id);
            if ($available <= 0) {
                return $this->errorResult($translations['error']['upper']);
            }

            $is_refundable = $this->dependencies->getValidators()['payment']->isRefundableAmount($amount, $available);
            if (!$is_refundable['result']) {
                return $this->errorResult($translations['error'][$is_refundable['code']]);
            }

            // Recorded before the call: a refund the API processed but whose response never came
            // back still counts as refunded, so a retry can't refund it twice. Nothing has moved
            // yet, so a failure here is a plain error.
            $intent_id = self::INTENT_ID_PREFIX . uniqid('', true);
            if (!$refund_repository->addRefund($intent_id, $payment_operation->operationId, (string) $order->id, $amount, $currency_iso)) {
                $logger->error('UnifiedRefundAction::refundAction - Refund of ' . $amount . ' for order ' . $order->id
                    . ' not sent: its pending row could not be recorded.');

                return $this->errorResult($translations['error']['default']);
            }

            try {
                $response = $factory->create()->createRefund(
                    $payment_id,
                    $account_id,
                    // Mirrors the orderId sent when the payment was created (the cart id).
                    (string) $order->id_cart,
                    'Refund for order ' . $order->reference,
                    // submerchantExternalId only applies to EUR configurations, which UHF never uses.
                    null,
                    $amount,
                    $currency_iso
                );
            } catch (\Throwable $exception) {
                if ($this->isDefiniteFailure($exception)) {
                    $logger->error('UnifiedRefundAction::refundAction - UHF refund failed for order ' . $order->id
                        . ' (' . get_class($exception) . '): ' . $exception->getMessage());
                    $this->markFailed($refund_repository, $logger, $intent_id, $order->id);

                    return $this->errorResult($translations['error']['default']);
                }

                return $this->uncertainResult($logger, $translations, $intent_id, $amount, $order->id, get_class($exception) . ': ' . $exception->getMessage());
            }

            $decoded = json_decode((string) $response['body'], true);

            // A 2xx response still carries the refund's own execCode ("0000" on success).
            $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : null;
            if (null === $exec_code) {
                // Still accepted - refusing a 2xx would invite a second real refund - but logged:
                // it would mean the API contract changed. Only the keys, as the body may carry
                // payment-method details.
                $logger->error('UnifiedRefundAction::refundAction - 2xx refund response for order ' . $order->id
                    . ' carries no execCode, assumed accepted (response keys: '
                    . (is_array($decoded) ? implode(', ', array_keys($decoded)) : 'none, not JSON') . ')');
            } else {
                $classification = UpcRefundExecCode::classify($exec_code);
                if (UpcRefundExecCode::DECLINED === $classification) {
                    // A synchronous decline moved no money: the order state is left untouched.
                    $logger->error('UnifiedRefundAction::refundAction - Refund of ' . $amount . ' declined for order ' . $order->id
                        . ' (execCode ' . $exec_code . ')');
                    $this->markFailed($refund_repository, $logger, $intent_id, $order->id);

                    return $this->errorResult($translations['error']['default']);
                }
                if (UpcRefundExecCode::UNKNOWN === $classification) {
                    return $this->uncertainResult($logger, $translations, $intent_id, $amount, $order->id, 'undocumented execCode ' . $exec_code);
                }
            }

            // From here the money has moved at PayPlug: nothing below may turn into an error
            // result, a failure message would invite a second real refund.
            $refund_operation_id = $this->extractRefundOperationId($decoded);
            if (null === $refund_operation_id) {
                // Only the keys: the body may carry payment-method details.
                $logger->error('UnifiedRefundAction::refundAction - Refund accepted for order ' . $order->id
                    . ' but the response carried no refund operation id, its notification cannot be matched'
                    . ' (kept as ' . $intent_id . ', response keys: ' . (is_array($decoded) ? implode(', ', array_keys($decoded)) : 'none, not JSON') . ')');
            } else {
                try {
                    $bound = $refund_repository->bindRefundOperationId($intent_id, $refund_operation_id);
                } catch (\Throwable $exception) {
                    $bound = false;
                }
                if (!$bound) {
                    // The row still counts as refunded under its intent id, only its notification
                    // can't be matched.
                    $logger->error('UnifiedRefundAction::refundAction - Refund ' . $refund_operation_id . ' of ' . $amount
                        . ' for order ' . $order->id . ' succeeded at PayPlug but its id was not recorded locally (row '
                        . $intent_id . '), manual check needed.');
                }
            }

            // Inside the lock, so a concurrent partial and full refund can't leave the wrong state.
            $reload = $this->updateOrderState($order, $available - $amount, $logger);
        } catch (\Throwable $exception) {
            $logger->error('UnifiedRefundAction::refundAction - UHF refund failed for order ' . $order->id
                . ' (' . get_class($exception) . '): ' . $exception->getMessage());

            return $this->errorResult($translations['error']['default']);
        } finally {
            $lock->release($lock_key);
        }

        // The refund is recorded: a rendering failure must not surface as a refund error, or the
        // merchant may retry and refund twice. Fall back to a full page reload instead.
        try {
            $template = $this->dependencies->hookClass->displayAdminOrderMain(['id_order' => (int) $order->id]);
        } catch (\Throwable $exception) {
            $logger->error('UnifiedRefundAction::refundAction - Refund recorded for order ' . $order->id
                . ' but the order panel could not be rendered (' . get_class($exception) . '): ' . $exception->getMessage());
            $template = '';
            $reload = true;
        }

        return [
            'result' => true,
            'data' => '',
            'template' => $template,
            'message' => $translations['success'],
            'modal' => '',
            'reload' => $reload,
        ];
    }

    /**
     * @description Move the order to the (partial) refund state; a failure is logged, never raised
     *
     * @param object $order
     * @param int $remaining refundable amount left after this refund
     * @param object $logger
     *
     * @return bool whether the order state changed (the BO page must reload)
     */
    private function updateOrderState($order, $remaining, $logger)
    {
        try {
            $plugin = $this->dependencies->getPlugin();
            $configuration = $plugin->getConfigurationClass();
            $state_addons = (bool) $configuration->getValue('sandbox_mode') ? '_test' : '';
            $state_key = $remaining > 0 ? 'order_state_partial_refund' : 'order_state_refund';
            $new_state = (int) $configuration->getValue($state_key . $state_addons);
            $reload = (int) $order->current_state !== $new_state;
            $plugin->getOrderClass()->updateOrderState($order, $new_state);

            return $reload;
        } catch (\Throwable $exception) {
            $logger->error('UnifiedRefundAction::refundAction - Refund succeeded for order ' . $order->id
                . ' but its order state could not be updated (' . get_class($exception) . '): ' . $exception->getMessage());

            return false;
        }
    }

    /**
     * @description Whether a createRefund() exception proves the refund was not processed: a
     *              local validation error, or a 4xx answer. A timeout (status 0), a 5xx or a
     *              409 leave the outcome unknown.
     *
     * @param \Throwable $exception
     *
     * @return bool
     */
    private function isDefiniteFailure($exception)
    {
        if ($exception instanceof InvalidRefundRequestException
            || $exception instanceof RefundAmountException
            || $exception instanceof PaymentNotFoundException) {
            return true;
        }

        if ($exception instanceof ApiException) {
            $status = (int) $exception->getCode();

            return $status >= 400 && $status < 500 && !in_array($status, [408, 409], true);
        }

        return false;
    }

    /**
     * @description Free the amount of a refund that did not go through; when that fails, the row
     *              stays pending (still counted as refunded), the safe direction
     *
     * @param UpcRefundRepository $refund_repository
     * @param object $logger
     * @param string $intent_id
     * @param int $id_order
     */
    private function markFailed($refund_repository, $logger, $intent_id, $id_order)
    {
        try {
            $failed = $refund_repository->updateStatusIfPending($intent_id, UpcRefundRepository::STATUS_FAILED);
        } catch (\Throwable $exception) {
            $failed = false;
        }
        if (!$failed) {
            $logger->error('UnifiedRefundAction::refundAction - Refund row ' . $intent_id . ' of order ' . $id_order
                . ' could not be marked failed, its amount stays blocked: manual check needed.');
        }
    }

    /**
     * @description The refund may have gone through: its row stays pending, the order state is
     *              left untouched, and the merchant is asked to check the portal before retrying
     *
     * @param object $logger
     * @param string $intent_id
     * @param int $amount
     * @param int $id_order
     * @param string $reason
     *
     * @return array{result: bool, message: string}
     */
    private function uncertainResult($logger, array $translations, $intent_id, $amount, $id_order, $reason)
    {
        $logger->error('UnifiedRefundAction::refundAction - Outcome of the refund of ' . $amount . ' for order ' . $id_order
            . ' is unknown (' . $reason . '): kept pending as ' . $intent_id . ', manual check needed.');

        return $this->errorResult($translations['error']['uncertain']);
    }

    /**
     * @description Extract the refund's own operation id from the createRefund() response.
     *              Only operationIds[0]: the top-level "id" is the refunded PAYMENT's id, shared
     *              by every refund of that payment (confirmed against the staging API).
     *
     * @param mixed $decoded
     *
     * @return string|null
     */
    private function extractRefundOperationId($decoded)
    {
        if (is_array($decoded) && isset($decoded['operationIds'][0]) && is_string($decoded['operationIds'][0]) && '' !== $decoded['operationIds'][0]) {
            return $decoded['operationIds'][0];
        }

        return null;
    }

    /**
     * @param string $message
     *
     * @return array{result: bool, message: string}
     */
    private function errorResult($message)
    {
        return [
            'result' => false,
            'message' => $message,
        ];
    }
}
