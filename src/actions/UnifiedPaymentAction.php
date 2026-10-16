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
use PayPlug\src\application\adapter\PrestashopAdapter17;
use PayPlug\src\models\repositories\UnifiedRefundRepository;
use PayPlug\src\unified\helpers\UnifiedRefundExecCode;
use PayPlug\src\utilities\services\Props;
use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Dto\AddressDto;
use PayplugUnifiedCore\Dto\BillingDto;
use PayplugUnifiedCore\Dto\BrowserDto;
use PayplugUnifiedCore\Dto\CommonFieldsDto;
use PayplugUnifiedCore\Dto\ContactDto;
use PayplugUnifiedCore\Dto\CustomerDto;
use PayplugUnifiedCore\Dto\HostedFieldDto;
use PayplugUnifiedCore\Dto\PaymentDto;
use PayplugUnifiedCore\Dto\ShippingDto;
use PayplugUnifiedCore\Exceptions\ApiException;
use PayplugUnifiedCore\Exceptions\InvalidNotificationException;
use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;
use PayplugUnifiedCore\Utilities\Helpers\ExecCodeMapper;
use PayplugUnifiedCore\Utilities\Helpers\WebhookNotificationHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

class UnifiedPaymentAction
{
    use ServiceGetter;

    private const FACTORY_SERVICE = 'payplug.unified.payment_service_factory';
    // Covers a payment creation or order creation round-trip: a lock expiring mid-request could be stolen.
    private const ORDER_CREATION_LOCK_TTL = 60;
    private const LOCK_RETRY_ATTEMPTS = 2;
    private const LOCK_RETRY_DELAY_USEC = 200000;

    /**
     * notifyAction()'s own budget (~8s): its payment notification typically lands while
     * returnAction() is still creating the order under the same cart lock, and the Receiver does
     * not retry a 409 (observed on staging, 2026-09-29) - giving up after returnAction()'s short
     * budget meant the notification was simply lost.
     */
    private const NOTIFY_LOCK_RETRY_ATTEMPTS = 40;
    private const MISSING_EXEC_CODE = 'MISSING';
    // Longer than the 600s pending-operation entries, to still cover a late notification.
    private const PENDING_ALIAS_TTL = 86400;
    // Only probes the refund lock (released at once): it never guards anything itself.
    private const REFUND_LOCK_PROBE_TTL = 5;
    private const REFUND_REPOSITORY_SERVICE = 'payplug.models.repositories.unified_refund';

    public $dependencies;

    /**
     * Lazily-built, publicly overridable (tests assign a mock here directly, the same way they
     * already assign $this->dependencies, rather than going through the real UnifiedOrderAction
     * constructor - see orderAction()).
     *
     * @var \PayPlug\src\actions\UnifiedOrderAction|null
     */
    public $orderAction;

    /**
     * Delay between two lock attempts, in microseconds (a property so tests don't really wait).
     */
    public $lockRetryDelayUsec = self::LOCK_RETRY_DELAY_USEC;

    /**
     * @description Build the module dependencies for this action
     */
    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Dispatch the process to the valid Action in this class
     *
     * @param mixed $method
     * @param mixed $parameters
     */
    public function dispatchAction($method = '', $parameters = [])
    {
        if (!is_string($method) || !$method) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $method must be a non empty string.',
            ];
        }

        if (!is_array($parameters)) {
            return [
                'result' => false,
                'message' => 'Invalid argument, $parameters must be a valid array.',
            ];
        }

        $method_name = $method . 'Action';
        if (!is_callable([$this, $method_name])) {
            return [
                'result' => false,
                'message' => 'Method not found in object UnifiedPaymentAction',
            ];
        }

        try {
            return $this->{$method_name}($parameters);
        } catch (\Exception $exception) {
            $this->dependencies->getPlugin()->getLogger()->addLog(
                'UnifiedPaymentAction::dispatchAction - Exception thrown: ' . $exception->getMessage(),
                'error'
            );

            // The raw exception message must never reach the buyer: controllers/front/unified.php
            // exit(json_encode(...))s this "message" verbatim to the front-end, which displays it
            // as-is. Anything thrown outside a narrower, already-generic-message try/catch (a
            // getService() failure, UnifiedOrderAction::createFromOutcome(), a DB error in the
            // cache/lock repositories, an ApiException thrown outside the protected
            // createPayment() call...) could otherwise leak a raw API error body or infra/account
            // configuration straight to the customer's screen. The full message is still logged
            // server-side above, unchanged.
            return [
                'result' => false,
                'message' => $this->dependencies->l('An unexpected error occurred. Please try again.', 'uhf'),
            ];
        }
    }

    /**
     * @description Create the Unified API payment from the hosted fields token or a saved alias for the current cart
     *
     * @param mixed $params
     */
    public function createAction($params = [])
    {
        // The internal id_payplug_alias, never the Unified API alias id: that one stays server-side.
        $alias_param = isset($params['id_payplug_alias']) && is_string($params['id_payplug_alias']) ? $params['id_payplug_alias'] : '';
        // Strict digits: '12abc' must not be read as alias 12; anything else resolves to 0 and
        // is refused like an unknown alias by findUsable().
        $id_payplug_alias = ctype_digit($alias_param) ? (int) $alias_param : 0;
        $hf_token = isset($params['hfToken']) && is_string($params['hfToken']) ? $params['hfToken'] : '';
        $selected_brand = isset($params['selectedBrand']) && is_string($params['selectedBrand']) ? $params['selectedBrand'] : '';

        // id_payplug_alias wins when both are sent (the front never sends both). A future classic
        // Retail API card payment would be another elseif here.
        if ('' !== $alias_param) {
            // Saved-alias payment: no hfToken/selectedBrand needed.
        } elseif ($hf_token && $selected_brand) {
            if (!in_array(strtolower($selected_brand), PrestashopAdapter17::HOSTED_FIELDS_ACCEPTED_BRANDS, true)) {
                return [
                    'result' => false,
                    'message' => $this->dependencies->l('This card brand is not supported.', 'uhf'),
                ];
            }
        } else {
            return ['result' => false, 'message' => 'Missing hfToken/selectedBrand or id_payplug_alias'];
        }

        if (!$this->isTrustedCreateRequest($params)) {
            return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
        }

        if (!$this->dependencies->configClass->isValidFeature('feature_hosted_fields')) {
            return ['result' => false, 'message' => 'This payment method is not available'];
        }

        $id_cart = isset($params['id_cart']) ? (int) $params['id_cart'] : 0;
        if ($id_cart <= 0) {
            return ['result' => false, 'message' => 'Missing or invalid id_cart'];
        }

        $plugin = $this->dependencies->getPlugin();
        $context = $this->getContext();
        if (!$context || !isset($context->cart)) {
            return $this->errorResult('Invalid cart');
        }

        if ($id_cart !== (int) $context->cart->id) {
            return ['result' => false, 'message' => 'Invalid cart'];
        }

        $cart = $plugin->getCart()->get($id_cart);
        if (!$cart || !(int) $cart->id) {
            return ['result' => false, 'message' => 'Invalid cart'];
        }

        $currency = $plugin->getCurrency()->getCurrency((int) $cart->id_currency);
        $prestashop_adapter = $this->dependencies->loadAdapterPresta();
        if (!$currency
            || !isset($currency->iso_code)
            || 'EUR' === strtoupper((string) $currency->iso_code)
            || !$prestashop_adapter
            || !$prestashop_adapter->isHostedFieldsIdentifierConfigured($currency->iso_code)) {
            return ['result' => false, 'message' => 'This payment method is not available'];
        }

        if ($this->hasLiveOrder($plugin, $cart)) {
            return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
        }

        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();
        $token_cache = $factory->createTokenCache();

        // Before reconciliation, locking and any Unified API call; one generic message for
        // one-click disabled, guest, unknown, foreign, other-currency and other-identifier aliases
        // (no enumeration).
        if ('' !== $alias_param) {
            if (!$this->isOneClickEnabled()) {
                $logger->error('UnifiedPaymentAction::createAction - Alias payment refused, one-click disabled, cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            if ($this->isGuestCustomer($context)) {
                $logger->error('UnifiedPaymentAction::createAction - Alias payment refused for guest cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            $usable_alias = $this->getService('payplug.models.repositories.unified_alias')->findUsable(
                $id_payplug_alias,
                (int) $cart->id_customer,
                strtolower((string) $currency->iso_code),
                (string) $prestashop_adapter->getHostedFieldsIdentifier($currency->iso_code)
            );
            if (null === $usable_alias) {
                $logger->error('UnifiedPaymentAction::createAction - Unknown or unusable alias for cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            if ($this->getService('payplug.action.unified_alias')->isExpired($usable_alias)) {
                $logger->error('UnifiedPaymentAction::createAction - Alias payment refused, expired card, cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }
        }

        // Reconcile an already in-flight payment for this cart before creating a new one: a
        // customer who abandons a 3DS challenge and hits back/refresh (or opens a second tab) has
        // no order yet, while the first payment can still complete through the abandoned
        // challenge - creating another one would leave two live payments.
        $pending_operation_id = $token_cache->get('uhf_pending_operation:' . $cart->id);
        if ($pending_operation_id) {
            $reconciliation_result = $this->reconcilePendingOperation($factory, $logger, $cart, $context, $pending_operation_id);
            if (null !== $reconciliation_result) {
                return $reconciliation_result;
            }
            // else: getOperation() itself failed (e.g. the operation genuinely expired/vanished
            // server-side) - don't hard-fail the whole checkout over a reconciliation-check
            // failure, fall through and create a fresh payment below instead.
        }

        $lock = $factory->createLock();
        $lock_key = $this->lockKeyForCart($cart->id);

        if (!$lock->acquire($lock_key, self::ORDER_CREATION_LOCK_TTL)) {
            return [
                'result' => false,
                'message' => $this->dependencies->l('Please wait, your previous request is still being processed.', 'uhf'),
            ];
        }

        // Checked again under the lock: a concurrent request for this cart may have created its
        // order or its payment between the checks above and the lock, and would be charged twice.
        try {
            $order_created_meanwhile = $this->hasLiveOrder($plugin, $cart);
            $concurrent_operation_id = $order_created_meanwhile
                ? null
                : $token_cache->get('uhf_pending_operation:' . $cart->id);
        } catch (\Throwable $exception) {
            $lock->release($lock_key);

            throw $exception;
        }

        if ($order_created_meanwhile) {
            $lock->release($lock_key);

            return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
        }

        // The operation already handled above, if any, could not be reconciled: only a new one counts.
        if ($concurrent_operation_id && $concurrent_operation_id !== $pending_operation_id) {
            // Released first: reconciliation takes the same cart lock to create the order.
            $lock->release($lock_key);
            $reconciliation_result = $this->reconcilePendingOperation($factory, $logger, $cart, $context, $concurrent_operation_id);

            return null !== $reconciliation_result
                ? $reconciliation_result
                : $this->errorResult($this->dependencies->l('Please wait, your previous request is still being processed.', 'uhf'));
        }

        // The lock is released exactly once on every exit path, including an exception thrown
        // after the payment was created (e.g. a DB error in UnifiedTokenCache::set()), which would
        // otherwise lock the cart out for the whole TTL.
        try {
            try {
                $amount_cents = (int) $this->dependencies->getHelpers()['amount']->convertAmount((float) $cart->getOrderTotal(true, \Cart::BOTH), false, (string) $currency->iso_code);
                $token = bin2hex(random_bytes(32));
                $return_url = $context->link->getModuleLink(
                    $this->dependencies->name,
                    'unified',
                    ['action' => 'return', 'token' => $token],
                    true
                );

                $common = new CommonFieldsDto(
                    $prestashop_adapter->getHostedFieldsIdentifier($currency->iso_code),
                    $amount_cents,
                    (string) $currency->iso_code,
                    (string) $cart->id,
                    null
                );
                $common->successUrl = $return_url;
                $common->cancelUrl = $return_url;
                // Part of the payload the UHF technical doc specifies (and the Woo/Sylius modules
                // send), although the platform ignores it: notifications go to the Receiver
                // configured per realm (see notify.php), not to this URL.
                $common->notificationUrl = $context->link->getModuleLink($this->dependencies->name, 'notify', [], true);
                $common->description = 'Payment with unified hosted fields for cart ' . (string) $cart->id;

                // Billing/shipping feed the Unified API's 3DS risk scoring: without them,
                // frictionless-eligible payments likely get an unnecessary challenge. Enrichment
                // only: left null when unresolvable (e.g. no invoice address yet on the cart).
                [$billing, $shipping] = $this->buildBillingAndShipping($plugin, $cart);
                if (null !== $billing) {
                    $common->billing = $billing;
                }
                if (null !== $shipping) {
                    $common->shipping = $shipping;
                }

                $props = new Props();
                $browser = new BrowserDto(
                    (string) $plugin->getTools()->tool('getRemoteAddr'),
                    (string) $props->getServerProp('HTTP_REFERER'),
                    (string) $props->getServerProp('HTTP_USER_AGENT')
                );

                $customer_object = $plugin->getCustomer()->get((int) $cart->id_customer);
                $customer = new CustomerDto(
                    (string) $cart->id_customer,
                    isset($customer_object->email) ? (string) $customer_object->email : ''
                );

                if ('' !== $alias_param) {
                    $save_card = false;
                    $output = $factory->create()->createPayment(new PaymentDto(
                        $common,
                        (string) $usable_alias['alias_id'],
                        'ONE_CLICK',
                        $browser,
                        $customer,
                        null
                    ));
                } else {
                    $payment_method_details = ['selectedBrand' => strtolower($selected_brand)];
                    $payment_method = ['details' => $payment_method_details];
                    $recurring_mode = null;
                    $cardholder = $this->getCardholder($params);
                    // Ignored (payment still proceeds) without cardholder, with one-click disabled or for a guest.
                    $save_card = !empty($params['save_card'])
                        && '' !== $cardholder
                        && $this->isOneClickEnabled()
                        && !$this->isGuestCustomer($context);

                    if ($save_card) {
                        $payment_method_details['fullName'] = $cardholder;
                        $payment_method = [
                            'details' => $payment_method_details,
                            'saveFutureUsage' => true,
                        ];
                        $recurring_mode = 'ONE_CLICK';
                    }

                    $output = $factory->create()->createPayment(new HostedFieldDto(
                        $common,
                        $hf_token,
                        $recurring_mode,
                        $browser,
                        $customer,
                        $payment_method
                    ));
                }
            } catch (\Exception $exception) {
                // A timeout, a conflict or a server error may come after the payment was created: the customer
                // must not be told the card was not charged, nor be pushed into paying again.
                if ($this->isUncertainCreationFailure($exception)) {
                    $logger->error('UnifiedPaymentAction::createAction - createPayment outcome unknown for cart ' . $cart->id
                        . ': ' . $exception->getMessage());

                    return $this->errorResult($this->dependencies->l('We could not confirm your payment. Please check with your bank before trying again.', 'uhf'));
                }

                $logger->error('UnifiedPaymentAction::createAction - createPayment failed for cart ' . $cart->id . ': ' . $exception->getMessage());

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            $decoded_body = json_decode($output->body, true);
            $operation_id = $this->extractOperationId($decoded_body);

            if ('' === $operation_id) {
                $logger->error('UnifiedPaymentAction::createAction - missing operation identifier in payment creation response');

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            // Only a hfToken payment with an effective save_card creates an alias ($save_card is
            // always false on the alias branch, whose $output->aliasId is a mere echo). The alias id
            // may still be empty here: it is then resolved from getOperation() later.
            $pending_alias = null;
            if ($save_card) {
                $pending_alias = [
                    'alias_id' => is_string($output->aliasId) ? $output->aliasId : '',
                    'brand' => strtolower($selected_brand),
                ];
            }

            $this->bindPaymentReferences($factory, $logger, $decoded_body, $operation_id, (int) $cart->id, $amount_cents);

            if ($output->redirectHtml || $output->redirectUrl) {
                $token_cache->set('uhf_pending_operation:' . $cart->id, $operation_id, 600);

                // Read-after-write check: UnifiedTokenCache::set() can't report a write failure through
                // ITokenCache's void return type, and the return/challenge flow depends on finding
                // this entry - without it the customer would land on the error page once charged.
                if ($operation_id !== $token_cache->get('uhf_pending_operation:' . $cart->id)) {
                    $logger->error('UnifiedPaymentAction::createAction - Failed to persist pending operation cache for cart ' . $cart->id);

                    return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
                }

                // Both directions of the token<->cart mapping are cached, same 600s TTL as the
                // pending-operation entry they're tied to: uhf_token_cart resolves an inbound
                // return/challenge request to its cart (returnAction()/challengeAction()), while
                // uhf_cart_token lets reconcilePendingOperation() rebuild the SAME challenge URL
                // when createAction() is re-entered for a cart that already has a live challenge.
                $token_cache->set('uhf_token_cart:' . $token, (string) $cart->id, 600);
                $token_cache->set('uhf_cart_token:' . $cart->id, $token, 600);

                if (null !== $pending_alias) {
                    $this->cachePendingAlias($token_cache, $logger, $operation_id, $pending_alias);
                }

                if ($output->redirectHtml) {
                    $token_cache->set('uhf_challenge_html:' . $cart->id, (string) $output->redirectHtml, 600);
                    $redirect_url = $context->link->getModuleLink(
                        $this->dependencies->name,
                        'unified',
                        ['action' => 'challenge', 'token' => $token],
                        true
                    );
                } else {
                    $redirect_url = $output->redirectUrl;
                }

                return $this->withReturnUrl([
                    'result' => true,
                    'redirect_url' => $redirect_url,
                ]);
            }

            $exec_code = is_array($decoded_body) && isset($decoded_body['execCode'])
                ? (string) $decoded_body['execCode']
                : self::MISSING_EXEC_CODE;
            $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);

            // A pending card is never saved at creation: the marker lets the return/notification
            // save it once PAID. A direct PAID saves it below, once the order exists and the lock
            // is released, from the operation fetched back.
            if (null !== $pending_alias && PaymentOutcome::THREE_DS_PENDING === $outcome) {
                $this->cachePendingAlias($token_cache, $logger, $operation_id, $pending_alias);
            }

            // PRE-3557 rule: no order for a payment still pending, the notification creates it once paid.
            if (PaymentOutcome::THREE_DS_PENDING === $outcome) {
                $logger->info('UnifiedPaymentAction::createAction - Operation ' . $operation_id . ' still pending 3DS, no order created for cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            $result = $this->orderAction()->createFromOutcome(
                (int) $cart->id,
                $operation_id,
                $exec_code,
                $outcome,
                $amount_cents,
                null,
                []
            );

            if (!$result['result']) {
                $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                    $this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'),
                ]);
            }

            $direct_paid_alias = null !== $pending_alias && PaymentOutcome::PAID === $outcome && $result['result']
                ? $pending_alias
                : null;
            $response = $this->withReturnUrl($result);
        } finally {
            $lock->release($lock_key);
        }

        if (null !== $direct_paid_alias) {
            $this->persistDirectPaidAlias($factory, $logger, (int) $cart->id, $operation_id, $direct_paid_alias);
        }

        return $response;
    }

    /**
     * @description Return the cached 3DS challenge HTML for the cart resolved from the token
     *
     * @param mixed $params
     */
    public function challengeAction($params = [])
    {
        $factory = $this->getService(self::FACTORY_SERVICE);
        $token_cache = $factory->createTokenCache();

        $token = isset($params['token']) && is_string($params['token']) ? $params['token'] : '';
        $id_cart = $this->resolveIdCartFromToken($token_cache, $token);
        if ($id_cart <= 0) {
            $factory->createLogger()->error('UnifiedPaymentAction::challengeAction - Invalid or expired token');

            return ['result' => false];
        }

        $html = $token_cache->get('uhf_challenge_html:' . $id_cart);

        if (null === $html || '' === $html) {
            $factory->createLogger()->error('UnifiedPaymentAction::challengeAction - No pending 3DS challenge for cart ' . $id_cart);

            return ['result' => false];
        }

        return ['result' => true, 'html' => $html];
    }

    /**
     * @description Resolve the 3DS return outcome and create the order for the tokenized cart
     *
     * @param mixed $params
     */
    public function returnAction($params = [])
    {
        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();
        $token_cache = $factory->createTokenCache();
        $order_action = $this->orderAction();

        $token = isset($params['token']) && is_string($params['token']) ? $params['token'] : '';
        $id_cart = $this->resolveIdCartFromToken($token_cache, $token);
        if ($id_cart <= 0) {
            $logger->error('UnifiedPaymentAction::returnAction - Invalid or expired token');

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => $order_action->errorUrl(),
            ]);
        }

        // Cheap local check first: if a concurrent webhook (notifyAction) already created the
        // order for this cart, skip the external getOperation() call and the lock wait below
        // entirely - nothing left for this synchronous return check to do.
        $existing_order_redirect = $order_action->existingOrderRedirect($id_cart);
        if (null !== $existing_order_redirect) {
            $token_cache->delete('uhf_token_cart:' . $token);
            $token_cache->delete('uhf_cart_token:' . $id_cart);

            return $this->withReturnUrl($existing_order_redirect);
        }

        $operation_id = $token_cache->get('uhf_pending_operation:' . $id_cart);

        if (!$operation_id) {
            $logger->error('UnifiedPaymentAction::returnAction - No pending UPC operation for cart ' . $id_cart);

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => $order_action->errorUrl(),
            ]);
        }

        try {
            $response = $factory->create()->getOperation($operation_id);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction::returnAction - getOperation failed: ' . $exception->getMessage());

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => $order_action->errorUrl(),
            ]);
        }

        $decoded = json_decode($response['body'], true);
        $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : self::MISSING_EXEC_CODE;
        $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);

        // PRE-3557 rule, as on the Retail return: no order for a payment neither paid nor failed,
        // and the customer is sent back to the checkout. The cache entries are kept for a later
        // return, a retry (reconcilePendingOperation()) or the notification of the final outcome.
        if (PaymentOutcome::THREE_DS_PENDING === $outcome) {
            $logger->info('UnifiedPaymentAction::returnAction - Operation ' . $operation_id . ' still pending 3DS, no order created for cart ' . $id_cart);
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'),
            ]);

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => $order_action->errorUrl(),
            ]);
        }

        $amount = is_array($decoded) && isset($decoded['amount']) ? (int) $decoded['amount'] : null;

        if (null === $amount) {
            $outcome = PaymentOutcome::FAILED;
            $logger->error('UnifiedPaymentAction::returnAction - missing amount field in getOperation response for operation ' . $operation_id);
            $amount = 0;
        } else {
            // Without full signature verification, the orderId+amount cross-check is the only
            // protection against trusting both fields blindly from an external response.
            $order_id_from_response = is_array($decoded) && isset($decoded['orderId']) ? (string) $decoded['orderId'] : null;
            $cart = $this->dependencies->getPlugin()->getCart()->get($id_cart);

            $requested_amount = $this->getRequestedAmount($factory, $logger, $operation_id);

            if (!$this->crossChecksOrderIdAndAmount($logger, $cart, $id_cart, $order_id_from_response, $amount, 'UnifiedPaymentAction::returnAction', $operation_id, $requested_amount)) {
                return $this->crossCheckMismatchResult($order_action);
            }
        }

        if (PaymentOutcome::PAID === $outcome) {
            $this->backfillPaymentId($factory, $logger, $operation_id, $decoded, $id_cart);
        }

        $pending_alias = $this->resolvePendingAlias($token_cache, $operation_id, $decoded);
        $card_details = null !== $pending_alias ? $this->extractCardDetails($decoded) : [];

        $result = $this->createOrderWithLock($factory, $id_cart, $operation_id, $exec_code, $outcome, $amount, $pending_alias, $card_details);

        if ($result['result'] || PaymentOutcome::FAILED === $outcome) {
            // Single-use once resolved: closes the replay window on this token even if it leaked
            // (referrer header, browser history) after the customer reached a terminal outcome.
            $token_cache->delete('uhf_token_cart:' . $token);
            $token_cache->delete('uhf_cart_token:' . $id_cart);
        }

        if (!$result['result']) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'),
            ]);
        }

        return $this->withReturnUrl($result);
    }

    /**
     * @description Handle the Unified API webhook notification and create or update the order
     *
     * @param mixed $params
     */
    public function notifyAction($params = [])
    {
        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();

        $body = (string) $this->dependencies->getPlugin()->getTools()->tool('file_get_contents', 'php://input');
        $headers = $this->resolveAuthorizationHeader();
        $expected_header = (string) ($factory->createConfigurationRepository()->get('webhook_authorization_header') ?? '');

        // WebhookNotificationHelper::parse() only accepts a strict id/execCode/orderId/amount
        // payload shape, and ExecCodeMapper::toPaymentOutcome() - the only source of
        // $operation_data->outcome - never produces anything other than PAID/THREE_DS_PENDING/FAILED:
        // PaymentOutcome::REFUNDED is unreachable here. A refund initiated from the BO through the
        // Unified API does arrive on this webhook, though, with a payment-shaped body (execCode 0000,
        // orderId = cart), so it parses as a PAID payment: it is diverted by the payplug_unified_refund
        // lookup below, before the payment path. A THREE_DS_PENDING outcome is a no-op (PRE-3557
        // rule: no order for a payment still pending): only the final outcome creates the order.
        try {
            $operation_data = WebhookNotificationHelper::parse($headers, $body, $expected_header);
        } catch (InvalidNotificationException $exception) {
            $logger->error('UnifiedPaymentAction::notifyAction - Invalid notification: ' . $exception->getMessage());

            return ['http_status' => 400];
        }

        if (PaymentOutcome::THREE_DS_PENDING === $operation_data->outcome) {
            return ['http_status' => 200];
        }

        // A refund initiated from the BO is notified on this same Receiver, with a payment-shaped
        // body (execCode 0000, orderId = cart): it must never reach the payment path below, which
        // would push the refunded order back to paid.
        $refund_repository = $this->getService(self::REFUND_REPOSITORY_SERVICE);
        $refund = $refund_repository->getByRefundOperationId($operation_data->operationId);
        if (null !== $refund) {
            return $this->handleRefundNotification($factory, $logger, $refund_repository, $refund, $operation_data->operationId);
        }

        $payment_repository = $factory->createPaymentRepository();
        if ($payment_repository->isTreated($operation_data->operationId)) {
            return $this->backfillTreatedOperation($factory, $logger, $payment_repository, $operation_data->operationId);
        }

        try {
            $response = $factory->create()->getOperation($operation_data->operationId);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction::notifyAction - getOperation failed: ' . $exception->getMessage());

            return ['http_status' => 500];
        }

        $decoded = json_decode($response['body'], true);
        $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : self::MISSING_EXEC_CODE;
        $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);

        if (PaymentOutcome::THREE_DS_PENDING === $outcome) {
            return ['http_status' => 200];
        }

        $order_id_from_response = is_array($decoded) && isset($decoded['orderId']) ? (string) $decoded['orderId'] : null;
        $id_cart = (int) $order_id_from_response;
        if ($id_cart <= 0) {
            $logger->error('UnifiedPaymentAction::notifyAction - Invalid orderId in getOperation response: ' . $order_id_from_response);

            return ['http_status' => 400];
        }

        $plugin = $this->dependencies->getPlugin();
        $cart = $plugin->getCart()->get($id_cart);
        $amount = is_array($decoded) && isset($decoded['amount']) ? (int) $decoded['amount'] : null;

        $has_amount = null !== $amount;
        if (!$has_amount) {
            $outcome = PaymentOutcome::FAILED;
            $logger->error('UnifiedPaymentAction::notifyAction - missing amount field in getOperation response for operation ' . $operation_data->operationId);
            $amount = 0;
        }

        // Before the cart-amount cross-check: a partial refund notification never matches the
        // cart total, and would otherwise be dropped there before getting a chance to be matched.
        $id_order = (int) $plugin->getOrder()->getIdByCartId($id_cart);
        if ($this->isPaidByAnotherOperation($payment_repository, $id_order, $operation_data->operationId)) {
            // Most likely a refund notification racing refundAction(), which holds the refund lock
            // until the refund's row carries its operation id: wait for it, then match again.
            $refund = $this->waitForRefundRecording($factory, $refund_repository, $id_order, $operation_data->operationId);
            if (null !== $refund) {
                return $this->handleRefundNotification($factory, $logger, $refund_repository, $refund, $operation_data->operationId);
            }

            if (PaymentOutcome::PAID === $outcome) {
                // Still unmatched: a refund whose row never got its operation id, a refund made
                // outside the module, or a second payment of the same cart. Never applied onto the
                // paid order. The Receiver does not retry a 409 (observed on staging, 2026-09-29),
                // so this notification is not delivered again: the log is the only trace.
                $logger->error('UnifiedPaymentAction::notifyAction - Operation ' . $operation_data->operationId
                    . ' is not the paid operation of the order of cart ' . $id_cart
                    . ' and matches no recorded refund (early refund notification or duplicate payment), manual check needed');

                return ['http_status' => 409];
            }

            // A 409 here would make the Receiver loop on the late webhook of an earlier failed
            // attempt, and a non-paid outcome has nothing to apply on an already-paid order.
            $logger->info('UnifiedPaymentAction::notifyAction - Non-paid operation ' . $operation_data->operationId
                . ' for the already-paid order of cart ' . $id_cart . ' ignored');

            return ['http_status' => 200];
        }

        if ($has_amount && !$this->crossChecksOrderIdAndAmount(
            $logger,
            $cart,
            $id_cart,
            $order_id_from_response,
            $amount,
            'UnifiedPaymentAction::notifyAction',
            $operation_data->operationId,
            $this->getRequestedAmount($factory, $logger, $operation_data->operationId)
        )) {
            return ['http_status' => 200];
        }

        // $decoded is the re-fetched getOperation() response, never the unverified webhook $body.
        if (PaymentOutcome::PAID === $outcome) {
            $this->backfillPaymentId($factory, $logger, $operation_data->operationId, $decoded, $id_cart);
        }

        $pending_alias = $this->resolvePendingAlias($factory->createTokenCache(), $operation_data->operationId, $decoded);
        $card_details = null !== $pending_alias ? $this->extractCardDetails($decoded) : [];

        $result = $this->createOrderWithLock(
            $factory,
            $id_cart,
            $operation_data->operationId,
            $exec_code,
            $outcome,
            $amount,
            $pending_alias,
            $card_details,
            self::NOTIFY_LOCK_RETRY_ATTEMPTS
        );

        if (!empty($result['lock_conflict'])) {
            return ['http_status' => 409];
        }

        // Any outcome, FAILED included: an unapplied FAILED leaves the existing order in the wrong state.
        // Whether the Receiver retries a 5xx is unconfirmed; the operation stays untreated, so a
        // redelivery is reprocessed.
        if (isset($result['outcome_applied']) && false === $result['outcome_applied']) {
            $logger->error('UnifiedPaymentAction::notifyAction - Outcome ' . $outcome . ' of operation ' . $operation_data->operationId
                . ' could not be applied to the order of cart ' . $id_cart);

            return ['http_status' => 500];
        }

        if (!$result['result'] && PaymentOutcome::FAILED !== $outcome) {
            $logger->error('UnifiedPaymentAction::notifyAction - createFromOutcome failed for operation ' . $operation_data->operationId);

            return ['http_status' => 500];
        }

        if (PaymentOutcome::FAILED === $outcome && empty($result['persisted'])) {
            // Only needed when createFromOutcome() itself never persisted anything (its "no
            // existing order + FAILED" path returns early without saving) - without this,
            // markTreated() below would have no row to mark and isTreated() would keep returning
            // false forever for this operation. When createFromOutcome() DID persist (the
            // existing-order path, via persistAndApplyOutcome() - see 'persisted' above), that
            // row is already correctly keyed to the real PrestaShop order id; blindly re-saving
            // $operation_data here would silently overwrite that with the wrong value, which is
            // exactly the bug this guard prevents. OperationData requires a non-empty orderId, so
            // (same as this codebase's pre-existing, documented semantic inconsistency on this
            // column) the cart id is used as the best available placeholder when there is no
            // real order to reference.
            $factory->createPaymentRepository()->save(new OperationData(
                $operation_data->operationId,
                $exec_code,
                $outcome,
                $amount,
                (string) $id_cart
            ));
        }

        $payment_repository->markTreated($operation_data->operationId);

        return ['http_status' => 200];
    }

    /**
     * @description Bring a Unified Hosted Fields order left in the pending state up to date with
     *              its operation, when the order is opened in the back office
     *
     * A terminal outcome goes through createOrderWithLock(), as the return and the notification
     * do; a payment still pending, an unreadable operation or a failed cross-check leaves the
     * order as is.
     *
     * @param int $id_order
     *
     * @return bool true when the order left the pending state
     */
    public function resyncPendingOrder($id_order)
    {
        $plugin = $this->dependencies->getPlugin();
        $order = $plugin->getOrder()->get((int) $id_order);
        if (!is_object($order) || !isset($order->module) || $order->module !== $this->dependencies->name) {
            return false;
        }

        $is_live = !(bool) $plugin->getConfigurationClass()->getValue('sandbox_mode');
        $order_states = $plugin->getOrderClass()->getOrderStates($is_live);
        $pending_state = isset($order_states['pending']) ? (int) $order_states['pending'] : 0;
        if (!$pending_state || $pending_state !== (int) $order->current_state) {
            return false;
        }

        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();
        $operation_id = $this->findOrderOperationId($factory->createPaymentRepository(), (int) $id_order);
        if ('' === $operation_id) {
            return false;
        }

        try {
            $response = $factory->create()->getOperation($operation_id);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction::resyncPendingOrder - getOperation failed for order ' . (int) $id_order
                . ' (operation ' . $operation_id . '): ' . $exception->getMessage());

            return false;
        }

        $decoded = json_decode($response['body'], true);
        $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : self::MISSING_EXEC_CODE;
        $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);
        if (PaymentOutcome::THREE_DS_PENDING === $outcome) {
            return false;
        }

        if (!is_array($decoded) || !isset($decoded['amount'])) {
            $logger->error('UnifiedPaymentAction::resyncPendingOrder - missing amount field in getOperation response for operation '
                . $operation_id . ', order ' . (int) $id_order . ' left as is');

            return false;
        }

        $amount = (int) $decoded['amount'];
        $id_cart = (int) $order->id_cart;
        $order_id_from_response = isset($decoded['orderId']) ? (string) $decoded['orderId'] : null;
        if (!$this->crossChecksOrderIdAndAmount(
            $logger,
            $plugin->getCart()->get($id_cart),
            $id_cart,
            $order_id_from_response,
            $amount,
            'UnifiedPaymentAction::resyncPendingOrder',
            $operation_id,
            $this->getRequestedAmount($factory, $logger, $operation_id)
        )) {
            return false;
        }

        if (PaymentOutcome::PAID === $outcome) {
            $this->backfillPaymentId($factory, $logger, $operation_id, $decoded, $id_cart);
        }

        $pending_alias = $this->resolvePendingAlias($factory->createTokenCache(), $operation_id, $decoded);
        $card_details = null !== $pending_alias ? $this->extractCardDetails($decoded) : [];

        $result = $this->createOrderWithLock($factory, $id_cart, $operation_id, $exec_code, $outcome, $amount, $pending_alias, $card_details);
        if (!empty($result['lock_conflict'])) {
            return false;
        }

        if (empty($result['persisted']) || (isset($result['outcome_applied']) && false === $result['outcome_applied'])) {
            $logger->error('UnifiedPaymentAction::resyncPendingOrder - Outcome ' . $outcome . ' of operation ' . $operation_id
                . ' could not be applied to order ' . (int) $id_order);

            return false;
        }

        $factory->createPaymentRepository()->markTreated($operation_id);

        $updated_order = $plugin->getOrder()->get((int) $id_order);

        return is_object($updated_order) && $pending_state !== (int) $updated_order->current_state;
    }

    /**
     * @description Settle a refund recorded by UnifiedRefundAction from its asynchronous
     *              notification. Never changes the order state: a refund failing asynchronously
     *              only becomes refundable again.
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param UnifiedRefundRepository $refund_repository
     * @param string $operation_id
     *
     * @return array{http_status: int}
     */
    private function handleRefundNotification($factory, $logger, $refund_repository, array $refund, $operation_id)
    {
        if (UnifiedRefundRepository::STATUS_PENDING !== $refund['status']) {
            return ['http_status' => 200];
        }

        try {
            $response = $factory->create()->getOperation($operation_id);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction::notifyAction - getOperation failed for refund ' . $operation_id . ': ' . $exception->getMessage());

            return ['http_status' => 500];
        }

        $decoded = json_decode($response['body'], true);
        if (!is_array($decoded) || !isset($decoded['execCode'])) {
            // Settling on a guess would either hide a failed refund or make a real one refundable again.
            $logger->error('UnifiedPaymentAction::notifyAction - Refund ' . $operation_id . ' getOperation response has no execCode');

            return ['http_status' => 500];
        }

        if (!isset($decoded['amount'])) {
            $logger->error('UnifiedPaymentAction::notifyAction - Refund ' . $operation_id . ' getOperation response has no amount');

            return ['http_status' => 500];
        }

        $amount = (int) $decoded['amount'];
        if ($amount !== (int) $refund['amount']) {
            $logger->error('UnifiedPaymentAction::notifyAction - Refund ' . $operation_id . ' amount mismatch: notified '
                . $amount . ', recorded ' . (int) $refund['amount']);

            return ['http_status' => 200];
        }

        // Not ExecCodeMapper: a code it doesn't know maps to FAILED, which would free an amount that
        // may already have left for a second refund. See UnifiedRefundExecCode.
        $exec_code = (string) $decoded['execCode'];
        if (UnifiedRefundExecCode::SUCCESS === $exec_code) {
            $refund_repository->updateStatusIfPending($operation_id, UnifiedRefundRepository::STATUS_CONFIRMED);

            return ['http_status' => 200];
        }

        $classification = UnifiedRefundExecCode::classify($exec_code);
        if (UnifiedRefundExecCode::ACCEPTED === $classification) {
            $logger->info('UnifiedPaymentAction::notifyAction - Refund ' . $operation_id . ' still in progress (execCode ' . $exec_code . ')');

            return ['http_status' => 200];
        }

        if (UnifiedRefundExecCode::UNKNOWN === $classification) {
            $logger->error('UnifiedPaymentAction::notifyAction - Refund ' . $operation_id . ' reported an undocumented execCode '
                . $exec_code . ', left pending (still counted as refunded): manual check needed');

            return ['http_status' => 200];
        }

        $logger->error('UnifiedPaymentAction::notifyAction - Refund ' . $operation_id . ' reported a non-success outcome (execCode '
            . $exec_code . '), marked failed: the order state is left unchanged');
        $refund_repository->updateStatusIfPending($operation_id, UnifiedRefundRepository::STATUS_FAILED);

        return ['http_status' => 200];
    }

    /**
     * @description Whether the cart's order is already paid by an operation other than this one.
     *              The only legitimate new operation on an existing order is the reconciliation
     *              of an abandoned (error state) order, which has no PAID operation.
     *
     * @param mixed $payment_repository
     * @param int $id_order the order of the notified cart, 0 when it has none
     * @param string $operation_id
     *
     * @return bool
     */
    private function isPaidByAnotherOperation($payment_repository, $id_order, $operation_id)
    {
        if (!$id_order) {
            return false;
        }

        $paid_operation = $payment_repository->getPaidByOrderId((string) $id_order);

        return null !== $paid_operation && $paid_operation->operationId !== $operation_id;
    }

    /**
     * @description Wait for a refund in progress on the order to record its operation id, then
     *              look the notified operation up again among the recorded refunds. The refund lock
     *              is only probed: acquired and released at once, as soon as it is free.
     *
     * @param mixed $factory
     * @param UnifiedRefundRepository $refund_repository
     * @param int $id_order
     * @param string $operation_id
     *
     * @return array|null the recorded refund, null when the operation matches none
     */
    private function waitForRefundRecording($factory, $refund_repository, $id_order, $operation_id)
    {
        $lock = $factory->createLock();
        $lock_key = UnifiedRefundAction::LOCK_KEY_PREFIX . (int) $id_order;

        for ($attempt = 0; $attempt <= self::NOTIFY_LOCK_RETRY_ATTEMPTS; ++$attempt) {
            if ($attempt > 0) {
                usleep($this->lockRetryDelayUsec);
            }

            if ($lock->acquire($lock_key, self::REFUND_LOCK_PROBE_TTL)) {
                $lock->release($lock_key);

                return $refund_repository->getByRefundOperationId($operation_id);
            }
        }

        return $refund_repository->getByRefundOperationId($operation_id);
    }

    /**
     * @description Get the operation recorded for an order, the paid one first
     *
     * @param mixed $payment_repository
     * @param int $id_order
     *
     * @return string empty when the order has no Unified Hosted Fields operation
     */
    private function findOrderOperationId($payment_repository, $id_order)
    {
        $paid_operation = $payment_repository->getPaidByOrderId((string) $id_order);
        if (null !== $paid_operation) {
            return (string) $paid_operation->operationId;
        }

        try {
            return (string) $payment_repository->getByOrderId((string) $id_order)->operationId;
        } catch (PaymentNotFoundException $exception) {
            return '';
        }
    }

    /**
     * @description Get the UnifiedOrderAction service, lazily loaded
     */
    private function orderAction()
    {
        if (null === $this->orderAction) {
            $this->orderAction = $this->getService('payplug.action.unified_order');
        }

        return $this->orderAction;
    }

    /**
     * @description Reconcile an in-flight payment for the cart instead of creating a new one
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param mixed $cart
     * @param mixed $context
     * @param mixed $pending_operation_id
     *
     * @return array|null the final result to return from createAction(), or null to fall through
     *                    to a fresh payment creation (reconciliation wasn't possible right now)
     */
    private function reconcilePendingOperation($factory, $logger, $cart, $context, $pending_operation_id)
    {
        try {
            $response = $factory->create()->getOperation($pending_operation_id);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction::reconcilePendingOperation - getOperation failed: ' . $exception->getMessage());

            return null;
        }

        $decoded = json_decode($response['body'], true);
        $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : self::MISSING_EXEC_CODE;
        $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);

        if (PaymentOutcome::THREE_DS_PENDING === $outcome) {
            // Still pending: don't create a new payment, send the customer back to the existing
            // challenge (a page refresh then resumes the in-flight challenge instead of starting
            // a duplicate one) - same challenge URL construction as the redirectHtml branch below,
            // reusing the SAME token createAction() originally minted for this cart (via the
            // uhf_cart_token reverse mapping) rather than id_cart, for the same reason
            // challengeAction()/returnAction() no longer trust a client-supplied id_cart.
            $token_cache = $factory->createTokenCache();
            $token = (string) $token_cache->get('uhf_cart_token:' . $cart->id);

            if ('' === $token) {
                $logger->error('UnifiedPaymentAction::reconcilePendingOperation - Missing token mapping for cart ' . $cart->id . ', cannot rebuild challenge URL');

                return null;
            }

            $redirect_url = $context->link->getModuleLink(
                $this->dependencies->name,
                'unified',
                ['action' => 'challenge', 'token' => $token],
                true
            );

            return $this->withReturnUrl([
                'result' => true,
                'redirect_url' => $redirect_url,
            ]);
        }

        $amount = is_array($decoded) && isset($decoded['amount']) ? (int) $decoded['amount'] : null;

        if (null === $amount) {
            $outcome = PaymentOutcome::FAILED;
            $logger->error('UnifiedPaymentAction::reconcilePendingOperation - missing amount field in getOperation response for operation ' . $pending_operation_id);
            $amount = 0;
        } else {
            // Same orderId+amount cross-check as returnAction().
            $order_id_from_response = is_array($decoded) && isset($decoded['orderId']) ? (string) $decoded['orderId'] : null;

            $requested_amount = $this->getRequestedAmount($factory, $logger, $pending_operation_id);

            if (!$this->crossChecksOrderIdAndAmount($logger, $cart, (int) $cart->id, $order_id_from_response, $amount, 'UnifiedPaymentAction::reconcilePendingOperation', $pending_operation_id, $requested_amount)) {
                return $this->crossCheckMismatchResult($this->orderAction());
            }
        }

        if (PaymentOutcome::PAID === $outcome) {
            $this->backfillPaymentId($factory, $logger, $pending_operation_id, $decoded, (int) $cart->id);
        }

        $pending_alias = $this->resolvePendingAlias($factory->createTokenCache(), $pending_operation_id, $decoded);
        $card_details = null !== $pending_alias ? $this->extractCardDetails($decoded) : [];

        $result = $this->createOrderWithLock($factory, (int) $cart->id, $pending_operation_id, $exec_code, $outcome, $amount, $pending_alias, $card_details);

        if (!empty($result['lock_conflict'])) {
            // Someone else (another request, or the async webhook) is concurrently finalizing
            // this exact operation right now - don't also fall through to create a brand-new
            // payment on top of that race; fail safe and let the customer retry.
            return $this->withReturnUrl($result);
        }

        if (PaymentOutcome::FAILED === $outcome) {
            return null;
        }

        return $this->withReturnUrl($result);
    }

    /**
     * @description Create the order from the outcome under the cart lock, then clear pending cache
     *
     * @param mixed $factory
     * @param mixed $id_cart
     * @param mixed $operation_id
     * @param mixed $exec_code
     * @param mixed $outcome
     * @param mixed $amount
     * @param array{alias_id: string, brand: string}|null $pending_alias
     * @param array<string, string|null> $card_details
     * @param int $retry_attempts lock attempts after the first one
     *
     * @return array{result: bool, redirect_url: string, persisted?: bool, outcome_applied?: bool, lock_conflict?: bool}
     */
    private function createOrderWithLock($factory, $id_cart, $operation_id, $exec_code, $outcome, $amount, $pending_alias = null, $card_details = [], $retry_attempts = self::LOCK_RETRY_ATTEMPTS)
    {
        $logger = $factory->createLogger();
        $order_action = $this->orderAction();
        $lock = $factory->createLock();
        $lock_key = $this->lockKeyForCart($id_cart);
        $lock_acquired = $lock->acquire($lock_key, self::ORDER_CREATION_LOCK_TTL);

        for ($attempt = 0; !$lock_acquired && $attempt < $retry_attempts; ++$attempt) {
            usleep($this->lockRetryDelayUsec);
            $lock_acquired = $lock->acquire($lock_key, self::ORDER_CREATION_LOCK_TTL);
        }

        if (!$lock_acquired) {
            // A concurrent call is holding the lock for this cart after a short bounded wait. Do
            // NOT proceed unlocked: createFromOutcome()'s order_exists guard is a non-atomic
            // check-then-act, so racing it against a still-running concurrent createFromOutcome()
            // call could create two orders for the same cart. Failing safe here (even though the
            // payment may well have succeeded on the other side) is preferable to that risk.
            $logger->error('UnifiedPaymentAction::createOrderWithLock - Could not acquire lock for cart ' . $id_cart);

            // 'lock_conflict' is an internal-only signal consumed by notifyAction() (to keep
            // returning 409 for this specific case, as before) - every other caller passes its
            // result through withReturnUrl(), which strips it, so it never leaks externally.
            return [
                'result' => false,
                'redirect_url' => $order_action->errorUrl(),
                'lock_conflict' => true,
            ];
        }

        try {
            $result = $order_action->createFromOutcome(
                $id_cart,
                $operation_id,
                $exec_code,
                $outcome,
                $amount,
                $pending_alias,
                $card_details
            );
        } finally {
            $lock->release($lock_key);
        }

        if ($result['result'] || PaymentOutcome::FAILED === $outcome) {
            $token_cache = $factory->createTokenCache();
            $token_cache->delete('uhf_pending_operation:' . $id_cart);
            $token_cache->delete('uhf_challenge_html:' . $id_cart);
        }

        // Not on a failed PAID attempt (retry possible): the entry then expires with its own TTL.
        // A PAID outcome whose alias could not be stored still clears it: a refused card leaves no trace.
        if ((PaymentOutcome::PAID === $outcome && $result['result']) || PaymentOutcome::FAILED === $outcome) {
            $factory->createTokenCache()->delete('uhf_pending_alias:' . $operation_id);
        }

        return $result;
    }

    /**
     * @description Whether a createPayment() exception leaves the payment outcome unknown: a timeout
     *              (status 0), a 408, a 409 or a 5xx, as for UnifiedRefundAction::isDefiniteFailure()
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    private function isUncertainCreationFailure($exception)
    {
        if (!$exception instanceof ApiException) {
            return false;
        }

        $status = (int) $exception->getCode();

        return 0 === $status || $status >= 500 || in_array($status, [408, 409], true);
    }

    /**
     * @description Tell whether a payment creation request is a POST carrying the customer static
     *              token, so a third-party page can't make the customer's browser charge their card (CSRF)
     *
     * @return bool
     */
    private function isTrustedCreateRequest(array $params)
    {
        $props = new Props();
        if ('POST' !== strtoupper((string) $props->getServerProp('REQUEST_METHOD'))) {
            return false;
        }

        $token = isset($params['static_token']) ? $params['static_token'] : '';

        return is_string($token)
            && '' !== $token
            && hash_equals((string) $this->dependencies->getPlugin()->getTools()->tool('getToken', false), $token);
    }

    /**
     * @description Tell whether the cart already has an order other than an abandoned PayPlug one
     *
     * @param mixed $plugin
     * @param mixed $cart
     *
     * @return bool
     */
    private function hasLiveOrder($plugin, $cart)
    {
        $order_adapter = $plugin->getOrder();
        $order = $order_adapter->get((int) $order_adapter->getIdByCartId((int) $cart->id));
        $order_exists = $this->dependencies->getValidators()['order']->isCreated($order, (int) $cart->id);

        return $order_exists['result'] && !$this->isAbandonedPayplugOrder($plugin, $order);
    }

    /**
     * @description Tell whether the order is a PayPlug order left in the error state
     *
     * @param mixed $plugin
     * @param mixed $order
     *
     * @return bool
     */
    private function isAbandonedPayplugOrder($plugin, $order)
    {
        if (!is_object($order) || !isset($order->module) || $order->module !== $this->dependencies->name) {
            return false;
        }

        $is_live = !(bool) $plugin->getConfigurationClass()->getValue('sandbox_mode');
        $order_states = $plugin->getOrderClass()->getOrderStates($is_live);
        $error_state = isset($order_states['error']) ? (int) $order_states['error'] : 0;
        $current_state = isset($order->current_state) ? (int) $order->current_state : 0;

        return $error_state > 0 && $current_state === $error_state;
    }

    /**
     * @description Tell whether the checkout context has no registered, non-guest customer
     *
     * @param mixed $context
     *
     * @return bool
     */
    private function isGuestCustomer($context)
    {
        $customer = is_object($context) && isset($context->customer) && is_object($context->customer)
            ? $context->customer
            : null;

        return !$customer || !isset($customer->id) || (int) $customer->id <= 0 || !empty($customer->is_guest);
    }

    /**
     * @description Tell whether the merchant enabled one-click (saved cards)
     *
     * @return bool
     */
    private function isOneClickEnabled()
    {
        $payment_methods = json_decode(
            (string) $this->dependencies->getPlugin()->getConfigurationClass()->getValue('payment_methods'),
            true
        );

        return is_array($payment_methods) && !empty($payment_methods['one_click']);
    }

    /**
     * @description Cache the save-card opt-in of an operation for its later return/notification
     *
     * Keyed by operation, so another attempt on the same cart can never read it. A write failure
     * only loses the saved card, never the payment.
     *
     * @param mixed $token_cache
     * @param mixed $logger
     * @param string $operation_id
     * @param array{alias_id: string, brand: string} $pending_alias
     */
    private function cachePendingAlias($token_cache, $logger, $operation_id, array $pending_alias)
    {
        try {
            $token_cache->set('uhf_pending_alias:' . $operation_id, (string) json_encode($pending_alias), self::PENDING_ALIAS_TTL);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction::createAction - Failed to cache pending alias for operation ' . $operation_id . ': ' . $exception->getMessage());
        }
    }

    /**
     * @description Check the response orderId matches the cart and its amount the one requested
     *
     * Cross-checks a getOperation()/notification response's orderId against the cart, and its
     * amount, in the minor units of the cart's currency, against the amount requested when the
     * payment was created. That reference is fixed: a cart edited during the 3DS challenge no
     * longer matches the amount captured, and the order is then created for what was paid
     * (UnifiedOrderAction flags the difference). An operation recorded without it is checked
     * against the live cart total instead. Without full signature verification, this is the only
     * protection against a forged or mismatched response. Logs both the expected and received
     * values on a mismatch.
     *
     * @param mixed $logger
     * @param mixed $cart
     * @param mixed $id_cart
     * @param mixed $response_order_id
     * @param mixed $response_amount
     * @param mixed $context_label
     * @param string $operation_id
     * @param int|null $requested_amount
     */
    private function crossChecksOrderIdAndAmount($logger, $cart, $id_cart, $response_order_id, $response_amount, $context_label, $operation_id, $requested_amount)
    {
        if (!$cart || !(int) $cart->id) {
            $logger->error($context_label . ' - Cart not found for cross-check, id_cart ' . $id_cart);

            return false;
        }

        if ((string) $id_cart !== (string) $response_order_id) {
            $logger->error($context_label . ' - orderId mismatch: expected ' . $id_cart . ', received ' . (string) $response_order_id);

            return false;
        }

        if (null !== $requested_amount) {
            if ($requested_amount !== (int) $response_amount) {
                $logger->error($context_label . ' - paid amount differs from the requested amount, manual review needed: operation '
                    . $operation_id . ' of cart ' . $id_cart . ' paid ' . (int) $response_amount . ', ' . $requested_amount
                    . ' requested at creation, no order created');

                return false;
            }

            return true;
        }

        $currency = $this->dependencies->getPlugin()->getCurrency()->getCurrency((int) $cart->id_currency);
        $currency_iso = is_object($currency) && isset($currency->iso_code) ? (string) $currency->iso_code : '';

        try {
            $expected_amount = (int) $this->dependencies->getHelpers()['amount']->convertAmount(
                (float) $cart->getOrderTotal(true, \Cart::BOTH),
                false,
                $currency_iso
            );
        } catch (\Exception $exception) {
            $logger->error($context_label . ' - cart ' . $id_cart . ' total could not be converted: ' . $exception->getMessage());

            return false;
        }

        if ($expected_amount !== (int) $response_amount) {
            $logger->error($context_label . ' - amount mismatch: expected ' . $expected_amount . ', received ' . (int) $response_amount);

            return false;
        }

        return true;
    }

    /**
     * @description Build the error result returned on a cross-check mismatch
     *
     * @param mixed $order_action
     *
     * @return array{result: bool, redirect_url: string}
     */
    private function crossCheckMismatchResult($order_action)
    {
        $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
            $this->dependencies->l('An unexpected error occurred. Please try again.', 'uhf'),
        ]);

        return $this->withReturnUrl([
            'result' => false,
            'redirect_url' => $order_action->errorUrl(),
        ]);
    }

    /**
     * @description Build the billing and shipping DTOs from the cart addresses
     *
     * Resolves the cart's invoice/delivery addresses (when set) into a BillingDto/ShippingDto pair
     * for the payment payload, with the same address resolution as the Retail API payment methods
     * (plugin->getAddress()->get($id_address), configClass->getIsoCodeByCountryId()).
     *
     * @param mixed $plugin
     * @param mixed $cart
     *
     * @return array{0: BillingDto|null, 1: ShippingDto|null}
     */
    private function buildBillingAndShipping($plugin, $cart)
    {
        $id_address_invoice = isset($cart->id_address_invoice) ? (int) $cart->id_address_invoice : 0;
        $id_address_delivery = isset($cart->id_address_delivery) ? (int) $cart->id_address_delivery : 0;

        $billing = null;
        $invoice_address = $this->resolveAddress($plugin, $id_address_invoice);
        if (null !== $invoice_address) {
            $billing = new BillingDto($invoice_address['address'], $invoice_address['contact']);
        }

        $shipping = null;
        $delivery_address = $this->resolveAddress($plugin, $id_address_delivery);
        if (null !== $delivery_address) {
            $shipping = new ShippingDto($delivery_address['address'], $delivery_address['contact']);
        }

        return [$billing, $shipping];
    }

    /**
     * @description Build the address and contact DTOs for the given address id
     *
     * @param mixed $plugin
     * @param mixed $id_address
     *
     * @return array{address: AddressDto, contact: ContactDto}|null
     */
    private function resolveAddress($plugin, $id_address)
    {
        if ($id_address <= 0) {
            return null;
        }

        $address = $plugin->getAddress()->get($id_address);
        if (!$address || !(int) $address->id) {
            return null;
        }

        $line = trim((string) $address->address1);
        if (!empty($address->address2)) {
            $line = trim($line . ' ' . (string) $address->address2);
        }

        $iso_code = $this->dependencies->configClass->getIsoCodeByCountryId((int) $address->id_country);

        $address_dto = new AddressDto(
            '' !== $line ? $line : null,
            !empty($address->city) ? (string) $address->city : null,
            '' !== $iso_code ? $iso_code : null,
            null,
            !empty($address->postcode) ? (string) $address->postcode : null
        );

        $contact_dto = new ContactDto(
            !empty($address->firstname) ? (string) $address->firstname : null,
            !empty($address->lastname) ? (string) $address->lastname : null,
            !empty($address->phone) ? (string) $address->phone : null,
            !empty($address->phone_mobile) ? (string) $address->phone_mobile : null
        );

        return ['address' => $address_dto, 'contact' => $contact_dto];
    }

    /**
     * @description Build the lock key for the given cart
     *
     * @param mixed $id_cart
     */
    private function lockKeyForCart($id_cart)
    {
        return 'uhf_cart:' . (int) $id_cart;
    }

    /**
     * @description Resolve the cart id from the opaque return token
     *
     * Resolves the opaque, high-entropy token minted by createAction() (see its own comment) back
     * to the id_cart it belongs to, via the token cache - the only way returnAction()/
     * challengeAction() learn which cart a request is for, since neither can trust a
     * client-supplied id_cart directly.
     *
     * @param mixed $token_cache
     * @param mixed $token
     *
     * @return int the resolved id_cart, or 0 when the token is empty/unknown/expired
     */
    private function resolveIdCartFromToken($token_cache, $token)
    {
        if ('' === $token) {
            return 0;
        }

        return (int) $token_cache->get('uhf_token_cart:' . $token);
    }

    /**
     * @description Read the alias pending creation for an operation, if any
     *
     * No valid cached marker means no save-card opt-in: nothing is saved. The alias id comes from
     * the module-fetched getOperation() response, else from the marker.
     *
     * @param mixed $token_cache
     * @param string $operation_id
     * @param mixed $decoded getOperation() response, never a webhook body
     *
     * @return array{alias_id: string, brand: string}|null
     */
    private function resolvePendingAlias($token_cache, $operation_id, $decoded)
    {
        $cached = $token_cache->get('uhf_pending_alias:' . $operation_id);
        $marker = is_string($cached) && '' !== $cached ? json_decode($cached, true) : null;

        if (!is_array($marker)
            || !isset($marker['alias_id'], $marker['brand'])
            || !is_string($marker['alias_id'])
            || !is_string($marker['brand'])) {
            return null;
        }

        $alias_id = $this->extractAliasId($decoded);
        if ('' === $alias_id) {
            $alias_id = $marker['alias_id'];
        }

        return '' === $alias_id ? null : ['alias_id' => $alias_id, 'brand' => $marker['brand']];
    }

    /**
     * @description Save the alias of a directly PAID operation from the operation fetched back
     *
     * Runs once the order exists and the cart lock is released: failure only costs the saved card.
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param int $id_cart
     * @param string $operation_id
     * @param array{alias_id: string, brand: string} $pending_alias
     */
    private function persistDirectPaidAlias($factory, $logger, $id_cart, $operation_id, array $pending_alias)
    {
        try {
            $response = $factory->create()->getOperation($operation_id);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction::createAction - getOperation failed, alias not saved for operation ' . $operation_id . ': ' . $exception->getMessage());

            return;
        }

        $decoded = is_array($response) && isset($response['body']) ? json_decode($response['body'], true) : null;
        if (!is_array($decoded)) {
            $logger->error('UnifiedPaymentAction::createAction - invalid getOperation response, alias not saved for operation ' . $operation_id);

            return;
        }

        $alias_id = $this->extractAliasId($decoded);
        if ('' !== $alias_id) {
            $pending_alias['alias_id'] = $alias_id;
        }
        if ('' === $pending_alias['alias_id']) {
            $logger->error('UnifiedPaymentAction::createAction - save_card requested but no alias id returned for operation ' . $operation_id);

            return;
        }

        $this->orderAction()->persistAlias($id_cart, $pending_alias, $this->extractCardDetails($decoded));
    }

    /**
     * @description Extract the card alias id from an operation response this module fetched
     *
     * The alias is paymentMethod.storedId, else paymentMethod.id; an empty or non-string value
     * is ignored and the next field is read.
     *
     * @param mixed $decoded
     *
     * @return string the alias id, or '' when absent
     */
    private function extractAliasId($decoded)
    {
        if (!is_array($decoded) || !isset($decoded['paymentMethod']) || !is_array($decoded['paymentMethod'])) {
            return '';
        }

        foreach (['storedId', 'id'] as $key) {
            if (isset($decoded['paymentMethod'][$key])
                && is_string($decoded['paymentMethod'][$key])
                && '' !== $decoded['paymentMethod'][$key]) {
                return $decoded['paymentMethod'][$key];
            }
        }

        return '';
    }

    /**
     * @description Extract the best-effort card details from an operation response
     *
     * Only ever called with a getOperation() response this module fetched itself, never with a
     * webhook body. Response shape: design doc §2.2 / open point §6.1.
     *
     * @param mixed $decoded
     *
     * @return array{last4: string|null, exp_month: string|null, exp_year: string|null}
     */
    private function extractCardDetails($decoded)
    {
        $details = ['last4' => null, 'exp_month' => null, 'exp_year' => null];
        $payment_method = is_array($decoded) && isset($decoded['paymentMethod']) && is_array($decoded['paymentMethod'])
            ? $decoded['paymentMethod']
            : [];

        $code6x4 = isset($payment_method['card']['code6x4']) && is_string($payment_method['card']['code6x4'])
            ? $payment_method['card']['code6x4']
            : '';
        if (1 === preg_match('/(\d{4})$/', $code6x4, $matches)) {
            $details['last4'] = $matches[1];
        }

        $validity_date = isset($payment_method['details']['validityDate']) && is_string($payment_method['details']['validityDate'])
            ? trim($payment_method['details']['validityDate'])
            : '';
        if (1 === preg_match('/^(\d{4})-(\d{2})$/', $validity_date, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
        } elseif (1 === preg_match('/^(\d{2})(\d{2})$/', $validity_date, $matches)) {
            $month = $matches[1];
            $year = '20' . $matches[2];
        } else {
            return $details;
        }

        if ((int) $month >= 1 && (int) $month <= 12) {
            $details['exp_month'] = $month;
            $details['exp_year'] = $year;
        }

        return $details;
    }

    /**
     * @description Get the Authorization header from the server variables
     */
    private function resolveAuthorizationHeader()
    {
        $props = new Props();
        $value = (string) $props->getServerProp('HTTP_AUTHORIZATION');

        if ('' === $value) {
            // Apache + mod_php commonly strips the Authorization header; a rewrite rule can
            // retransmit it under this alternate key instead.
            $value = (string) $props->getServerProp('REDIRECT_HTTP_AUTHORIZATION');
        }

        return '' === $value ? [] : ['Authorization' => $value];
    }

    /**
     * @description Build a failed result with the given message
     *
     * @param mixed $message
     */
    private function errorResult($message)
    {
        return [
            'result' => false,
            'message' => $message,
        ];
    }

    /**
     * @description Extract the operation id from the decoded API response body
     *
     * @param mixed $decoded_body
     */
    private function extractOperationId($decoded_body)
    {
        if (!is_array($decoded_body)) {
            return '';
        }

        if (isset($decoded_body['operationIds'][0]) && is_string($decoded_body['operationIds'][0])) {
            return $decoded_body['operationIds'][0];
        }

        if (isset($decoded_body['id']) && is_string($decoded_body['id'])) {
            return $decoded_body['id'];
        }

        return '';
    }

    /**
     * @description Record the payment's own id and the requested amount on its operation, right
     * after payment creation
     *
     * The refund endpoint is keyed by the payment's "id", which differs from "operationIds[0]"
     * (the operation_id every other flow uses). The requested amount is what the paid amount is
     * checked against. Without a payment id in the response, only the requested amount is
     * recorded, and the payment id is recorded later from the paid operation fetched back
     * (backfillPaymentId()), when it carries one. Never blocks the payment: it has already been
     * created at this point, so a failure is only logged.
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param mixed $decoded_body
     * @param string $operation_id
     * @param int $id_cart
     * @param int $requested_amount amount sent at creation, in minor units
     */
    private function bindPaymentReferences($factory, $logger, $decoded_body, $operation_id, $id_cart, $requested_amount)
    {
        $payment_id = is_array($decoded_body) && isset($decoded_body['id']) && is_string($decoded_body['id'])
            ? $decoded_body['id']
            : '';

        if ('' === $payment_id) {
            $logger->error('UnifiedPaymentAction::createAction - payment creation response for operation ' . $operation_id
                . ' carries no payment id: this payment cannot be refunded from the back-office until its operation provides one');

            try {
                $recorded = $factory->createPaymentRepository()->bindRequestedAmount($operation_id, (int) $requested_amount, $id_cart);
            } catch (\Throwable $exception) {
                $recorded = false;
            }

            if (!$recorded) {
                $logger->error('UnifiedPaymentAction::createAction - requested amount of operation ' . $operation_id
                    . ' (cart ' . $id_cart . ') not recorded: the paid amount will be checked against the cart total');
            }

            return;
        }

        try {
            $bound = $factory->createPaymentRepository()->bindPaymentId($operation_id, $payment_id, $id_cart, (int) $requested_amount);
        } catch (\Throwable $exception) {
            $bound = false;
        }

        if (!$bound) {
            $logger->error('UnifiedPaymentAction::createAction - payment id ' . $payment_id . ' of operation ' . $operation_id
                . ' (cart ' . $id_cart . ') not recorded: back-office refunds of this payment will fail');
        }
    }

    /**
     * @description Extract the payment id from an operation response this module fetched
     *
     * The payment id is the top-level "id" of an operation whose "operationType" is PAYMENT. Only
     * ever called with a getOperation() response, never with a webhook body: signature
     * verification is fail-open, and the payment id is what a back-office refund targets.
     *
     * @param mixed $decoded
     *
     * @return string the payment id, or '' when absent, malformed or not a payment's
     */
    private function extractPaymentId($decoded)
    {
        if (!is_array($decoded)
            || !isset($decoded['operationType'], $decoded['id'])
            || 'PAYMENT' !== $decoded['operationType']
            || !is_string($decoded['id'])) {
            return '';
        }

        return 1 === preg_match('/^[A-Za-z0-9_-]{1,255}\z/', $decoded['id']) ? $decoded['id'] : '';
    }

    /**
     * @description Record the payment id of a paid operation that has none, from the operation
     *              fetched back
     *
     * Called only for a PAID getOperation() response whose orderId and amount passed the
     * cross-check. An id equal to the operation id is not a payment id: the refund endpoint
     * rejects it. A recorded payment id is kept, even when it differs. Never blocks the caller:
     * a failure is only logged.
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param string $operation_id
     * @param mixed $decoded getOperation() response, never a webhook body
     * @param int $id_cart
     */
    private function backfillPaymentId($factory, $logger, $operation_id, $decoded, $id_cart)
    {
        $payment_id = $this->extractPaymentId($decoded);
        $is_operation_id = (string) $operation_id === $payment_id;
        if ($is_operation_id) {
            $payment_id = '';
        }

        try {
            $payment_repository = $factory->createPaymentRepository();
            $recorded = $payment_repository->getPaymentIdByOperationId($operation_id);
            if (null === $recorded && '' !== $payment_id) {
                if ($payment_repository->bindPaymentIdIfMissing($operation_id, $payment_id, (int) $id_cart)) {
                    $logger->info('UnifiedPaymentAction - payment id ' . $payment_id . ' of operation ' . $operation_id . ' recorded from the operation');

                    return;
                }

                $recorded = $payment_repository->getPaymentIdByOperationId($operation_id);
            }
        } catch (\Throwable $exception) {
            $logger->error('UnifiedPaymentAction - payment id ' . ('' !== $payment_id ? $payment_id . ' ' : '') . 'of operation ' . $operation_id
                . ' not recorded: back-office refunds of this payment will fail: ' . $exception->getMessage());

            return;
        }

        if (null !== $recorded) {
            if ('' !== $payment_id && $recorded !== $payment_id) {
                $logger->error('UnifiedPaymentAction - payment id mismatch for operation ' . $operation_id . ': ' . $recorded
                    . ' recorded, ' . $payment_id . ' in the operation, recorded value kept');
            }

            return;
        }

        if ('' === $payment_id) {
            $logger->info('UnifiedPaymentAction - paid operation ' . $operation_id . ' carries no payment id'
                . ($is_operation_id ? ' (its id is the operation id)' : '')
                . ': back-office refunds of this payment are not possible until one is recorded');

            return;
        }

        $logger->error('UnifiedPaymentAction - payment id ' . $payment_id . ' of operation ' . $operation_id
            . ' not recorded: back-office refunds of this payment will fail');
    }

    /**
     * @description Answer the redelivered notification of an already treated operation, recording
     *              its payment id from the operation fetched back when none is recorded yet
     *
     * No order work and no markTreated(): the answer is always 200.
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param mixed $payment_repository
     * @param string $operation_id
     *
     * @return array{http_status: int}
     */
    private function backfillTreatedOperation($factory, $logger, $payment_repository, $operation_id)
    {
        try {
            if (null !== $payment_repository->getPaymentIdByOperationId($operation_id)) {
                return ['http_status' => 200];
            }

            $response = $factory->create()->getOperation($operation_id);
            $decoded = json_decode($response['body'], true);
            $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : self::MISSING_EXEC_CODE;
            if (PaymentOutcome::PAID !== ExecCodeMapper::toPaymentOutcome($exec_code) || !isset($decoded['amount'])) {
                return ['http_status' => 200];
            }

            $order_id_from_response = isset($decoded['orderId']) ? (string) $decoded['orderId'] : null;
            $id_cart = (int) $order_id_from_response;
            if ($id_cart <= 0 || !$this->crossChecksOrderIdAndAmount(
                $logger,
                $this->dependencies->getPlugin()->getCart()->get($id_cart),
                $id_cart,
                $order_id_from_response,
                (int) $decoded['amount'],
                'UnifiedPaymentAction::notifyAction',
                $operation_id,
                $this->getRequestedAmount($factory, $logger, $operation_id)
            )) {
                return ['http_status' => 200];
            }

            $this->backfillPaymentId($factory, $logger, $operation_id, $decoded, $id_cart);
        } catch (\Throwable $exception) {
            $logger->error('UnifiedPaymentAction::notifyAction - payment id of treated operation ' . $operation_id
                . ' not recovered: ' . $exception->getMessage());
        }

        return ['http_status' => 200];
    }

    /**
     * @description Get the amount requested when the operation's payment was created
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param string $operation_id
     *
     * @return int|null null when none was recorded or it cannot be read
     */
    private function getRequestedAmount($factory, $logger, $operation_id)
    {
        try {
            return $factory->createPaymentRepository()->getRequestedAmountByOperationId($operation_id);
        } catch (\Exception $exception) {
            $logger->error('UnifiedPaymentAction - requested amount of operation ' . $operation_id
                . ' could not be read, checked against the cart total: ' . $exception->getMessage());

            return null;
        }
    }

    /**
     * @description Get the sanitized cardholder name from the request parameters
     */
    private function getCardholder(array $params)
    {
        $cardholder = '';
        if (isset($params['cardholder']) && is_string($params['cardholder'])) {
            $cardholder = $params['cardholder'];
        } elseif (isset($params['cardholderName']) && is_string($params['cardholderName'])) {
            $cardholder = $params['cardholderName'];
        }

        return trim((string) preg_replace('/[\r\n]+/', ' ', $cardholder));
    }

    /**
     * @description Get the current PrestaShop context
     */
    private function getContext()
    {
        $context_adapter = $this->dependencies->getPlugin()->getContext();

        return is_object($context_adapter) && method_exists($context_adapter, 'get')
            ? $context_adapter->get()
            : $context_adapter;
    }

    /**
     * @description Shape a browser-facing result: add return_url mirroring redirect_url, drop internal keys
     */
    private function withReturnUrl(array $result)
    {
        unset($result['persisted'], $result['lock_conflict'], $result['outcome_applied']);

        if (isset($result['redirect_url']) && !isset($result['return_url'])) {
            $result['return_url'] = $result['redirect_url'];
        }

        return $result;
    }
}
