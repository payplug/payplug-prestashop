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
use PayPlug\src\utilities\services\Props;
use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Dto\AddressDto;
use PayplugUnifiedCore\Dto\BillingDto;
use PayplugUnifiedCore\Dto\BrowserDto;
use PayplugUnifiedCore\Dto\CommonFieldsDto;
use PayplugUnifiedCore\Dto\ContactDto;
use PayplugUnifiedCore\Dto\CustomerDto;
use PayplugUnifiedCore\Dto\HostedFieldDto;
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
    // Shared by returnAction()'s and notifyAction()'s locking around order creation only -
    // createAction()'s own double-submit lock (guarding a shorter, unrelated critical section)
    // keeps its own literal 30s TTL and single acquire() attempt, unchanged.
    private const ORDER_CREATION_LOCK_TTL = 60;
    private const LOCK_RETRY_ATTEMPTS = 2;
    private const LOCK_RETRY_DELAY_USEC = 200000;
    // Sentinel used when an execCode is missing from a response - deliberately NOT starting with
    // '0000' (the literal success code) so it can never be mistaken for one by a future reader,
    // even though ExecCodeMapper::toPaymentOutcome() only ever does an exact string match.
    private const MISSING_EXEC_CODE = 'MISSING';

    public $dependencies;

    /**
     * Lazily-built, publicly overridable (tests assign a mock here directly, the same way they
     * already assign $this->dependencies, rather than going through the real UnifiedOrderAction
     * constructor - see orderAction()).
     *
     * @var \PayPlug\src\actions\UnifiedOrderAction|null
     */
    public $orderAction;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

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

    public function createAction($params = [])
    {
        $hf_token = isset($params['hfToken']) && is_string($params['hfToken']) ? $params['hfToken'] : '';
        $selected_brand = isset($params['selectedBrand']) && is_string($params['selectedBrand']) ? $params['selectedBrand'] : '';

        if (!$hf_token || !$selected_brand) {
            return ['result' => false, 'message' => 'Missing hfToken or selectedBrand'];
        }

        if (!in_array(strtolower($selected_brand), PrestashopAdapter17::HOSTED_FIELDS_ACCEPTED_BRANDS, true)) {
            return [
                'result' => false,
                'message' => $this->dependencies->l('This card brand is not supported.', 'uhf'),
            ];
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
        if ($order_exists['result']) {
            return $this->errorResult($this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'));
        }

        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();
        $token_cache = $factory->createTokenCache();

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

        if (!$lock->acquire($lock_key, 30)) {
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

                // Security fix (PRE-3626 review): returnAction()/challengeAction() used to trust a
                // client-supplied id_cart directly, with no proof the caller owns that cart. Since
                // both are reached via an unauthenticated cross-site ACS POST/redirect (no live
                // PrestaShop session to check against, unlike createAction()'s own
                // `$id_cart === $context->cart->id` guard), anyone able to guess/enumerate an
                // id_cart could hit returnAction() for someone else's cart - worst case, once that
                // cart's order already exists, UnifiedOrderAction::confirmationUrl()'s
                // secure_key+id_order (exactly what the order-confirmation controller accepts) leaks
                // to them via handleReturn()'s JSON branch. A high-entropy opaque token, generated
                // fresh per payment attempt and resolved server-side to its owning cart via the
                // token cache, replaces id_cart in the URLs below so return/challenge can no longer
                // be reached by guessing a cart id.
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

                $payment_method_details = ['selectedBrand' => strtolower($selected_brand)];
                $payment_method = ['details' => $payment_method_details];
                $recurring_mode = null;
                $save_card = !empty($params['save_card']);
                $cardholder = $this->getCardholder($params);

                if ($save_card && '' !== $cardholder) {
                    $payment_method_details['fullName'] = $cardholder;
                    $payment_method = [
                        'details' => $payment_method_details,
                        'saveFutureUsage' => true,
                    ];
                    // Fix 9 (PRE-3626 review): SUBSCRIPTION (not ONE_CLICK, as this project's own
                    // UHF technical design doc specifies) is used here deliberately - confirmed by
                    // this suite's own testCreateActionRequestsSavedCardWhenSaveCardAndCardholderAreGiven().
                    // The reason for diverging from the design doc isn't documented anywhere
                    // (no commit message, PR description, or docs/superpowers/ doc explains it) -
                    // flagging as a deliberate, confirmed-by-test choice pending confirmation from
                    // the team owning the Unified API contract, so a future reader doesn't "fix" it
                    // back to ONE_CLICK.
                    $recurring_mode = 'SUBSCRIPTION';
                }

                $output = $factory->create()->createPayment(new HostedFieldDto(
                    $common,
                    $hf_token,
                    $recurring_mode,
                    $browser,
                    $customer,
                    $payment_method
                ));
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
            $result = $this->orderAction()->createFromOutcome(
                (int) $cart->id,
                $operation_id,
                $exec_code,
                $outcome,
                $amount_cents
            );

            if (!$result['result']) {
                $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                    $this->dependencies->l('The transaction was not completed and your card was not charged.', 'uhf'),
                ]);
            }

            return $this->withReturnUrl($result);
        } finally {
            $lock->release($lock_key);
        }
    }

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
                $outcome = PaymentOutcome::FAILED;
            }
        }

        $result = $this->createOrderWithLock($factory, $id_cart, $operation_id, $exec_code, $outcome, $amount);
        unset($result['lock_conflict']);

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

    public function notifyAction($params = [])
    {
        $factory = $this->getService(self::FACTORY_SERVICE);
        $logger = $factory->createLogger();

        $body = (string) $this->dependencies->getPlugin()->getTools()->tool('file_get_contents', 'php://input');
        $headers = $this->resolveAuthorizationHeader();
        $expected_header = (string) ($factory->createConfigurationRepository()->get('webhook_authorization_header') ?? '');

        // Unified API "Payment Operation" notifications only - refunds and any other event type
        // are sent by the classic/GM API's own notifier (a different route: ipn.php), never here.
        // WebhookNotificationHelper::parse() enforces this structurally (it only ever accepts a
        // strict id/execCode/orderId/amount payload shape), and ExecCodeMapper::toPaymentOutcome()
        // - the only source of $operation_data->outcome - never produces anything other than
        // PAID/THREE_DS_PENDING/FAILED, so a refund can neither be parsed nor misclassified as one
        // of those three here. See UnifiedOrderAction::createFromOutcome()'s own comment for what
        // happens to an existing pending order for each of those three outcomes.
        try {
            $operation_data = WebhookNotificationHelper::parse($headers, $body, $expected_header);
        } catch (InvalidNotificationException $exception) {
            $logger->error('OperationAction::notifyAction - Invalid notification: ' . $exception->getMessage());

            return ['http_status' => 400];
        }

        if (PaymentOutcome::THREE_DS_PENDING === $operation_data->outcome) {
            return ['http_status' => 200];
        }

        $id_cart = (int) $operation_data->orderId;
        if ($id_cart <= 0) {
            $logger->error('OperationAction::notifyAction - Invalid orderId in notification: ' . $operation_data->orderId);

            return ['http_status' => 400];
        }

        $payment_repository = $factory->createPaymentRepository();
        if ($payment_repository->isTreated($operation_data->operationId)) {
            return ['http_status' => 200];
        }

        // Fix 4 (PRE-3626 review): the webhook payload has no separate id_cart to compare orderId
        // against (orderId IS the cart id here - there's no independent second source of cart
        // identity to cross-check it against in this server-to-server context), so only the
        // amount is cross-checked against the cart's own actual total.
        $plugin = $this->dependencies->getPlugin();
        $cart = $plugin->getCart()->get($id_cart);
        if (!$cart || !(int) $cart->id) {
            $logger->error('OperationAction::notifyAction - Cart not found for id_cart ' . $id_cart);

            return ['http_status' => 500];
        }

        $expected_amount = (int) $this->dependencies->getHelpers()['amount']->convertAmount((float) $cart->getOrderTotal(true, \Cart::BOTH));
        if ($expected_amount !== (int) $operation_data->amount) {
            $logger->error(
                'OperationAction::notifyAction - amount mismatch for cart ' . $id_cart
                . ': expected ' . $expected_amount . ', received ' . (int) $operation_data->amount
            );

            return ['http_status' => 500];
        }

        $result = $this->createOrderWithLock(
            $factory,
            $id_cart,
            $operation_data->operationId,
            $operation_data->execCode,
            $operation_data->outcome,
            $operation_data->amount
        );

        if (!empty($result['lock_conflict'])) {
            return ['http_status' => 409];
        }

        if (!$result['result'] && PaymentOutcome::FAILED !== $operation_data->outcome) {
            $logger->error('OperationAction::notifyAction - createFromOutcome failed for operation ' . $operation_data->operationId);

            return ['http_status' => 500];
        }

        if (PaymentOutcome::FAILED === $operation_data->outcome) {
            // Strictly necessary when no order exists yet for this cart: createFromOutcome()
            // returns without ever persisting a row in that case, so without this, markTreated()
            // below would have no row to mark and isTreated() would keep returning false forever
            // for this operation - saving it here is what makes the idempotency guard above
            // actually take effect on replay. When an order already exists,
            // createFromOutcome()'s existing-order branch has already saved this same row via
            // persistAndApplyOutcome() by the time we get here (upserted by operation_id) - this
            // call is then a harmless redundant write, not a functional no-op-turned-bug.
            $payment_repository->save($operation_data);
        }

        $payment_repository->markTreated($operation_data->operationId);

        return ['http_status' => 200];
    }

    private function orderAction()
    {
        if (null === $this->orderAction) {
            $this->orderAction = new UnifiedOrderAction($this->dependencies);
        }

        return $this->orderAction;
    }

    /**
     * Fix 2 (PRE-3626 review): reconciles an already in-flight UPC payment for this cart when
     * createAction() runs again while one is still pending, instead of letting execution proceed
     * to create a brand-new payment.
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
                $outcome = PaymentOutcome::FAILED;
            }
        }

        $result = $this->createOrderWithLock($factory, (int) $cart->id, $pending_operation_id, $exec_code, $outcome, $amount);
        unset($result['lock_conflict']);

        return $this->withReturnUrl($result);
    }

    /**
     * Fix 3 (PRE-3626 review): shared lock-acquire-with-bounded-retry + createFromOutcome() +
     * guaranteed-release helper, extracted out of returnAction()/notifyAction() (which used to
     * each inline a near-identical copy of this) and reused by the createAction() reconciliation
     * path (Fix 2) too. Deliberately separate from createAction()'s OWN double-submit lock
     * (uhf_cart:<cart>, 30s TTL, single acquire() attempt, guarding a different, shorter critical
     * section) - this one guards order creation itself, at ORDER_CREATION_LOCK_TTL/with retries.
     *
     * Fix 7 (PRE-3626 review): once a pending operation has been resolved to a terminal outcome
     * here (successfully, or a definitive FAILED), the uhf_pending_operation/uhf_challenge_html
     * cache entries have done their job - clears them so a later request for this cart doesn't
     * find stale state. Left alone when createFromOutcome() itself failed unexpectedly (e.g. a DB
     * error), so a retry can still find the pending operation.
     *
     * @param mixed $factory
     * @param mixed $id_cart
     * @param mixed $operation_id
     * @param mixed $exec_code
     * @param mixed $outcome
     * @param mixed $amount
     *
     * @return array{result: bool, redirect_url: string, lock_conflict?: bool}
     */
    private function createOrderWithLock($factory, $id_cart, $operation_id, $exec_code, $outcome, $amount)
    {
        $logger = $factory->createLogger();
        $order_action = $this->orderAction();
        $lock = $factory->createLock();
        $lock_key = $this->lockKeyForCart($id_cart);
        $lock_acquired = $lock->acquire($lock_key, self::ORDER_CREATION_LOCK_TTL);

        for ($attempt = 0; !$lock_acquired && $attempt < self::LOCK_RETRY_ATTEMPTS; ++$attempt) {
            usleep(self::LOCK_RETRY_DELAY_USEC);
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
            // returning 409 for this specific case, as before) - every other caller ignores it and
            // strips it before this array reaches a JSON response, so it never leaks externally.
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
                $amount
            );
        } finally {
            $lock->release($lock_key);
        }

        if ($result['result'] || PaymentOutcome::FAILED === $outcome) {
            $token_cache = $factory->createTokenCache();
            $token_cache->delete('uhf_pending_operation:' . $id_cart);
            $token_cache->delete('uhf_challenge_html:' . $id_cart);
        }

        return $result;
    }

    /**
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

    private function lockKeyForCart($id_cart)
    {
        return 'uhf_cart:' . (int) $id_cart;
    }

    /**
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

    private function errorResult($message)
    {
        return [
            'result' => false,
            'message' => $message,
        ];
    }

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

    private function getContext()
    {
        $context_adapter = $this->dependencies->getPlugin()->getContext();

        return is_object($context_adapter) && method_exists($context_adapter, 'get')
            ? $context_adapter->get()
            : $context_adapter;
    }

    private function withReturnUrl(array $result)
    {
        if (isset($result['redirect_url']) && !isset($result['return_url'])) {
            $result['return_url'] = $result['redirect_url'];
        }

        return $result;
    }
}
