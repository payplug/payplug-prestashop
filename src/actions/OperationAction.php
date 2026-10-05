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
use PayPlug\src\models\classes\UpcRefundExecCode;
use PayPlug\src\models\repositories\UpcRefundRepository;
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
use PayplugUnifiedCore\Exceptions\InvalidNotificationException;
use PayplugUnifiedCore\Utilities\Helpers\ExecCodeMapper;
use PayplugUnifiedCore\Utilities\Helpers\WebhookNotificationHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OperationAction
{
    use ServiceGetter;

    private const FACTORY_SERVICE = 'payplug.utilities.service.unified_api_payment_service_factory';
    // would let the lock expire and be stolen mid-request.
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
    private const REFUND_REPOSITORY_SERVICE = 'payplug.models.repositories.upc_refund';

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
                'message' => 'Method not found in object OperationAction',
            ];
        }

        try {
            return $this->{$method_name}($parameters);
        } catch (\Exception $exception) {
            $this->dependencies->getPlugin()->getLogger()->addLog(
                'OperationAction::dispatchAction - Exception thrown: ' . $exception->getMessage(),
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

        $order_adapter = $plugin->getOrder();
        $order = $order_adapter->get((int) $order_adapter->getIdByCartId((int) $cart->id));
        $order_exists = $this->dependencies->getValidators()['order']->isCreated($order, (int) $cart->id);
        if ($order_exists['result'] && !$this->isAbandonedPayplugOrder($plugin, $order)) {
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
                $logger->error('OperationAction::createAction - Alias payment refused, one-click disabled, cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            if ($this->isGuestCustomer($context)) {
                $logger->error('OperationAction::createAction - Alias payment refused for guest cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            $usable_alias = $this->getService('payplug.models.repositories.alias')->findUsable(
                $id_payplug_alias,
                (int) $cart->id_customer,
                strtolower((string) $currency->iso_code),
                (string) $prestashop_adapter->getHostedFieldsIdentifier($currency->iso_code)
            );
            if (null === $usable_alias) {
                $logger->error('OperationAction::createAction - Unknown or unusable alias for cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            if ($this->getService('payplug.action.alias')->isExpired($usable_alias)) {
                $logger->error('OperationAction::createAction - Alias payment refused, expired card, cart ' . $cart->id);

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }
        }

        // Fix 2 (PRE-3626 review): reconcile an already in-flight UPC payment for this cart
        // BEFORE acquiring the double-submit lock below and creating a brand-new payment.
        // Scenario this guards against: a customer gets a 3DS challenge, abandons it, then hits
        // back/refresh (or opens a second tab) - the PrestaShop order still doesn't exist (payment
        // not finalized), so without this check execution would sail past the order_exists check
        // above and call createPayment() a second time while the first payment can still
        // independently complete via the abandoned challenge, creating two live payments.
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

        // Fix 3 (PRE-3626 review): the entire flow from here through to the final return is now
        // wrapped so the lock is released exactly once, on every exit path (success, the
        // createPayment-specific error below, or any other exception) - previously, everything
        // past the inner catch (json_decode, extractOperationId, the token_cache->set() calls,
        // the final createFromOutcome() call) ran unprotected: an exception there (e.g. a DB error
        // inside UpcTokenCache::set()) would skip every $lock->release() call and leave the cart
        // locked out for the full 30s TTL.
        try {
            try {
                $amount_cents = (int) $this->dependencies->getHelpers()['amount']->convertAmount((float) $cart->getOrderTotal(true, \Cart::BOTH));
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

                // Fix 10 (PRE-3626 review): billing/shipping feed the Unified API's 3DS risk
                // scoring - omitting them likely pushes frictionless-eligible payments into an
                // unnecessary challenge. Enrichment only: left null when unresolvable (e.g. no
                // invoice address yet on the cart) rather than failing the payment over it.
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
                $logger->error('OperationAction::createAction - createPayment failed: ' . $exception->getMessage());

                return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
            }

            $decoded_body = json_decode($output->body, true);
            $operation_id = $this->extractOperationId($decoded_body);

            if ('' === $operation_id) {
                $logger->error('OperationAction::createAction - missing operation identifier in payment creation response');

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

            $this->bindPaymentId($factory, $logger, $decoded_body, $operation_id, (int) $cart->id);

            if ($output->redirectHtml || $output->redirectUrl) {
                $token_cache->set('uhf_pending_operation:' . $cart->id, $operation_id, 600);

                // Fix 6 (PRE-3626 review): cheap read-after-write check. UpcTokenCache::set() can't
                // propagate a write failure through ITokenCache's void return type, so without this
                // a silent DB error/truncation/duplicate key here would redirect the customer into
                // a flow (returnAction()/the challenge) that depends on finding this cache entry
                // and won't find anything - stranding them on the error page despite being charged.
                if ($operation_id !== $token_cache->get('uhf_pending_operation:' . $cart->id)) {
                    $logger->error('OperationAction::createAction - Failed to persist pending operation cache for cart ' . $cart->id);

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
            $factory->createLogger()->error('OperationAction::challengeAction - Invalid or expired token');

            return ['result' => false];
        }

        $html = $token_cache->get('uhf_challenge_html:' . $id_cart);

        if (null === $html || '' === $html) {
            $factory->createLogger()->error('OperationAction::challengeAction - No pending 3DS challenge for cart ' . $id_cart);

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
            $logger->error('OperationAction::returnAction - Invalid or expired token');

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
            $logger->error('OperationAction::returnAction - No pending UPC operation for cart ' . $id_cart);

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => $order_action->errorUrl(),
            ]);
        }

        try {
            $response = $factory->create()->getOperation($operation_id);
        } catch (\Exception $exception) {
            $logger->error('OperationAction::returnAction - getOperation failed: ' . $exception->getMessage());

            return $this->withReturnUrl([
                'result' => false,
                'redirect_url' => $order_action->errorUrl(),
            ]);
        }

        $decoded = json_decode($response['body'], true);
        $exec_code = is_array($decoded) && isset($decoded['execCode']) ? (string) $decoded['execCode'] : self::MISSING_EXEC_CODE;
        $outcome = ExecCodeMapper::toPaymentOutcome($exec_code);
        $amount = is_array($decoded) && isset($decoded['amount']) ? (int) $decoded['amount'] : null;

        if (null === $amount) {
            $outcome = PaymentOutcome::FAILED;
            $logger->error('OperationAction::returnAction - missing amount field in getOperation response for operation ' . $operation_id);
            $amount = 0;
        } else {
            // Fix 4 (PRE-3626 review): per this project's own UHF technical design doc, in the
            // absence of full signature verification "the only remaining protection is the
            // orderId+amount cross-check" - without it, both fields are trusted blindly from an
            // external response.
            $order_id_from_response = is_array($decoded) && isset($decoded['orderId']) ? (string) $decoded['orderId'] : null;
            $cart = $this->dependencies->getPlugin()->getCart()->get($id_cart);

            if (!$this->crossChecksOrderIdAndAmount($logger, $cart, $id_cart, $order_id_from_response, $amount, 'OperationAction::returnAction')) {
                return $this->crossCheckMismatchResult($order_action);
            }
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
        // orderId = cart), so it parses as a PAID payment: it is diverted by the payplug_upc_refund
        // lookup below, before the payment path. See UnifiedOrderAction::createFromOutcome()'s own
        // comment for what happens to an existing pending order for each of the three outcomes.
        try {
            $operation_data = WebhookNotificationHelper::parse($headers, $body, $expected_header);
        } catch (InvalidNotificationException $exception) {
            $logger->error('OperationAction::notifyAction - Invalid notification: ' . $exception->getMessage());

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
            return ['http_status' => 200];
        }

        try {
            $response = $factory->create()->getOperation($operation_data->operationId);
        } catch (\Exception $exception) {
            $logger->error('OperationAction::notifyAction - getOperation failed: ' . $exception->getMessage());

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
            $logger->error('OperationAction::notifyAction - Invalid orderId in getOperation response: ' . $order_id_from_response);

            return ['http_status' => 400];
        }

        $plugin = $this->dependencies->getPlugin();
        $cart = $plugin->getCart()->get($id_cart);
        $amount = is_array($decoded) && isset($decoded['amount']) ? (int) $decoded['amount'] : null;

        $has_amount = null !== $amount;
        if (!$has_amount) {
            $outcome = PaymentOutcome::FAILED;
            $logger->error('OperationAction::notifyAction - missing amount field in getOperation response for operation ' . $operation_data->operationId);
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
                $logger->error('OperationAction::notifyAction - Operation ' . $operation_data->operationId
                    . ' is not the paid operation of the order of cart ' . $id_cart
                    . ' and matches no recorded refund (early refund notification or duplicate payment), manual check needed');

                return ['http_status' => 409];
            }

            // A 409 here would make the Receiver loop on the late webhook of an earlier failed
            // attempt, and a non-paid outcome has nothing to apply on an already-paid order.
            $logger->info('OperationAction::notifyAction - Non-paid operation ' . $operation_data->operationId
                . ' for the already-paid order of cart ' . $id_cart . ' ignored');

            return ['http_status' => 200];
        }

        if ($has_amount && !$this->crossChecksOrderIdAndAmount($logger, $cart, $id_cart, $order_id_from_response, $amount, 'OperationAction::notifyAction')) {
            return ['http_status' => 200];
        }

        // $decoded is the re-fetched getOperation() response, never the unverified webhook $body.
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

        if (!$result['result'] && PaymentOutcome::FAILED !== $outcome) {
            $logger->error('OperationAction::notifyAction - createFromOutcome failed for operation ' . $operation_data->operationId);

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
     * @description Settle a refund recorded by UnifiedRefundAction from its asynchronous
     *              notification. Never changes the order state: a refund failing asynchronously
     *              only becomes refundable again.
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param UpcRefundRepository $refund_repository
     * @param string $operation_id
     *
     * @return array{http_status: int}
     */
    private function handleRefundNotification($factory, $logger, $refund_repository, array $refund, $operation_id)
    {
        if (UpcRefundRepository::STATUS_PENDING !== $refund['status']) {
            return ['http_status' => 200];
        }

        try {
            $response = $factory->create()->getOperation($operation_id);
        } catch (\Exception $exception) {
            $logger->error('OperationAction::notifyAction - getOperation failed for refund ' . $operation_id . ': ' . $exception->getMessage());

            return ['http_status' => 500];
        }

        $decoded = json_decode($response['body'], true);
        if (!is_array($decoded) || !isset($decoded['execCode'])) {
            // Settling on a guess would either hide a failed refund or make a real one refundable again.
            $logger->error('OperationAction::notifyAction - Refund ' . $operation_id . ' getOperation response has no execCode');

            return ['http_status' => 500];
        }

        if (!isset($decoded['amount'])) {
            $logger->error('OperationAction::notifyAction - Refund ' . $operation_id . ' getOperation response has no amount');

            return ['http_status' => 500];
        }

        $amount = (int) $decoded['amount'];
        if ($amount !== (int) $refund['amount']) {
            $logger->error('OperationAction::notifyAction - Refund ' . $operation_id . ' amount mismatch: notified '
                . $amount . ', recorded ' . (int) $refund['amount']);

            return ['http_status' => 200];
        }

        // Not ExecCodeMapper: a code it doesn't know maps to FAILED, which would free an amount that
        // may already have left for a second refund. See UpcRefundExecCode.
        $exec_code = (string) $decoded['execCode'];
        if (UpcRefundExecCode::SUCCESS === $exec_code) {
            $refund_repository->updateStatusIfPending($operation_id, UpcRefundRepository::STATUS_CONFIRMED);

            return ['http_status' => 200];
        }

        $classification = UpcRefundExecCode::classify($exec_code);
        if (UpcRefundExecCode::ACCEPTED === $classification) {
            $logger->info('OperationAction::notifyAction - Refund ' . $operation_id . ' still in progress (execCode ' . $exec_code . ')');

            return ['http_status' => 200];
        }

        if (UpcRefundExecCode::UNKNOWN === $classification) {
            $logger->error('OperationAction::notifyAction - Refund ' . $operation_id . ' reported an undocumented execCode '
                . $exec_code . ', left pending (still counted as refunded): manual check needed');

            return ['http_status' => 200];
        }

        $logger->error('OperationAction::notifyAction - Refund ' . $operation_id . ' reported a non-success outcome (execCode '
            . $exec_code . '), marked failed: the order state is left unchanged');
        $refund_repository->updateStatusIfPending($operation_id, UpcRefundRepository::STATUS_FAILED);

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
     * @param UpcRefundRepository $refund_repository
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
            $logger->error('OperationAction::reconcilePendingOperation - getOperation failed: ' . $exception->getMessage());

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
                $logger->error('OperationAction::reconcilePendingOperation - Missing token mapping for cart ' . $cart->id . ', cannot rebuild challenge URL');

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
            $logger->error('OperationAction::reconcilePendingOperation - missing amount field in getOperation response for operation ' . $pending_operation_id);
            $amount = 0;
        } else {
            // Fix 4 (PRE-3626 review): same orderId+amount cross-check as returnAction(), applied
            // here too - convenient since createAction() already has $cart in scope.
            $order_id_from_response = is_array($decoded) && isset($decoded['orderId']) ? (string) $decoded['orderId'] : null;

            if (!$this->crossChecksOrderIdAndAmount($logger, $cart, (int) $cart->id, $order_id_from_response, $amount, 'OperationAction::reconcilePendingOperation')) {
                return $this->crossCheckMismatchResult($this->orderAction());
            }
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
     * @return array{result: bool, redirect_url: string, persisted?: bool, lock_conflict?: bool}
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
            $logger->error('OperationAction::createOrderWithLock - Could not acquire lock for cart ' . $id_cart);

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

        // Not on THREE_DS_PENDING (the later PAID notification still needs it) nor on a failed
        // PAID attempt (retry possible): the entry then expires with its own TTL. A PAID outcome
        // whose alias could not be stored still clears it: a refused card leaves no trace.
        if ((PaymentOutcome::PAID === $outcome && $result['result']) || PaymentOutcome::FAILED === $outcome) {
            $factory->createTokenCache()->delete('uhf_pending_alias:' . $operation_id);
        }

        return $result;
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
            $logger->error('OperationAction::createAction - Failed to cache pending alias for operation ' . $operation_id . ': ' . $exception->getMessage());
        }
    }

    /**
     * @description Check the response orderId and amount match the cart
     *
     * Fix 4 (PRE-3626 review): cross-checks a getOperation()/notification response's orderId and
     * amount against the cart's own actual values - per this project's UHF technical design doc,
     * in the absence of full signature verification "the only remaining protection is the
     * orderId+amount cross-check". Logs both the expected and received values on a mismatch.
     *
     * @param mixed $logger
     * @param mixed $cart
     * @param mixed $id_cart
     * @param mixed $response_order_id
     * @param mixed $response_amount
     * @param mixed $context_label
     */
    private function crossChecksOrderIdAndAmount($logger, $cart, $id_cart, $response_order_id, $response_amount, $context_label)
    {
        if (!$cart || !(int) $cart->id) {
            $logger->error($context_label . ' - Cart not found for cross-check, id_cart ' . $id_cart);

            return false;
        }

        if ((string) $id_cart !== (string) $response_order_id) {
            $logger->error($context_label . ' - orderId mismatch: expected ' . $id_cart . ', received ' . (string) $response_order_id);

            return false;
        }

        $expected_amount = (int) $this->dependencies->getHelpers()['amount']->convertAmount((float) $cart->getOrderTotal(true, \Cart::BOTH));

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
     * Fix 10 (PRE-3626 review): resolves the cart's invoice/delivery addresses (when set) into a
     * BillingDto/ShippingDto pair for the payment payload - same address-resolution pattern
     * already used by OneyPaymentMethod/BancontactPaymentMethod/ApplepayPaymentMethod for the
     * Retail API flow (plugin->getAddress()->get($id_address), configClass->getIsoCodeByCountryId()).
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
     * client-supplied id_cart directly (see createAction()'s "Security fix" comment).
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
            $logger->error('OperationAction::createAction - getOperation failed, alias not saved for operation ' . $operation_id . ': ' . $exception->getMessage());

            return;
        }

        $decoded = is_array($response) && isset($response['body']) ? json_decode($response['body'], true) : null;
        if (!is_array($decoded)) {
            $logger->error('OperationAction::createAction - invalid getOperation response, alias not saved for operation ' . $operation_id);

            return;
        }

        $alias_id = $this->extractAliasId($decoded);
        if ('' !== $alias_id) {
            $pending_alias['alias_id'] = $alias_id;
        }
        if ('' === $pending_alias['alias_id']) {
            $logger->error('OperationAction::createAction - save_card requested but no alias id returned for operation ' . $operation_id);

            return;
        }

        $this->orderAction()->persistAlias($id_cart, $pending_alias, $this->extractCardDetails($decoded));
    }

    /**
     * @description Extract the card alias id from an operation response this module fetched
     *
     * The alias is paymentMethod.id, the same field the vendor maps to PaymentOutput::$aliasId.
     *
     * @param mixed $decoded
     *
     * @return string the alias id, or '' when absent
     */
    private function extractAliasId($decoded)
    {
        return is_array($decoded)
            && isset($decoded['paymentMethod']['id'])
            && is_string($decoded['paymentMethod']['id'])
            ? $decoded['paymentMethod']['id']
            : '';
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
     * @description Bind the payment's own id to its operation, right after payment creation
     *
     * The refund endpoint is keyed by the payment's "id", which differs from "operationIds[0]"
     * (the operation_id every other flow uses) and cannot be read back from the operation later.
     * Never blocks the payment: it has already been created at this point, so a failure only
     * costs the ability to refund it from the back-office, and is logged as such.
     *
     * @param mixed $factory
     * @param mixed $logger
     * @param mixed $decoded_body
     * @param string $operation_id
     * @param int $id_cart
     */
    private function bindPaymentId($factory, $logger, $decoded_body, $operation_id, $id_cart)
    {
        $payment_id = is_array($decoded_body) && isset($decoded_body['id']) && is_string($decoded_body['id'])
            ? $decoded_body['id']
            : '';

        if ('' === $payment_id) {
            $logger->error('OperationAction::createAction - payment creation response for operation ' . $operation_id
                . ' carries no payment id: this payment cannot be refunded from the back-office');

            return;
        }

        try {
            $bound = $factory->createPaymentRepository()->bindPaymentId($operation_id, $payment_id, $id_cart);
        } catch (\Throwable $exception) {
            $bound = false;
        }

        if (!$bound) {
            $logger->error('OperationAction::createAction - payment id ' . $payment_id . ' of operation ' . $operation_id
                . ' (cart ' . $id_cart . ') not recorded: back-office refunds of this payment will fail');
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
        unset($result['persisted'], $result['lock_conflict']);

        if (isset($result['redirect_url']) && !isset($result['return_url'])) {
            $result['return_url'] = $result['redirect_url'];
        }

        return $result;
    }
}
