<?php

namespace PayPlug\tests\actions\OperationAction;

use PayPlug\src\actions\OperationAction;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Output\PaymentOutput;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../stubs/Cart.php';

/**
 * @group unit
 * @group action
 * @group operation_action
 */
class createActionTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testCreateActionCachesPendingOperationWhenUnifiedApiReturnsRedirectUrlOnly()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->configClass = \Mockery::mock('ConfigClass');
        $dependencies->configClass->shouldReceive('isValidFeature')->with('feature_hosted_fields')->andReturn(true);
        $dependencies->shouldReceive('l')->andReturnUsing(function ($message) {
            return $message;
        });

        $plugin = \Mockery::mock('Plugin');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $customer_adapter = \Mockery::mock('CustomerAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');
        $amount_helper = \Mockery::mock('AmountHelper');
        $lock = \Mockery::mock('Lock');
        $logger = \Mockery::mock('Logger');
        $token_cache = \Mockery::mock('TokenCache');
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $factory = \Mockery::mock('Factory');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $link = \Mockery::mock('Link');

        $context = (object) [
            'cart' => (object) ['id' => 42],
            'link' => $link,
        ];
        $cart = new class() {
            public $id = 42;
            public $id_currency = 2;
            public $id_customer = 7;

            public function getOrderTotal($withTaxes, $type)
            {
                return 12.34;
            }
        };
        $currency = (object) ['iso_code' => 'USD'];
        $customer = (object) ['email' => 'john@example.com'];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $dependencies->shouldReceive('getHelpers')->andReturn([
            'amount' => $amount_helper,
            'cookies' => \Mockery::mock('CookiesHelper'),
        ]);

        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCustomer')->andReturn($customer_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getTools')->andReturnSelf();
        $plugin->shouldReceive('tool')->with('getRemoteAddr')->andReturn('127.0.0.1');

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);
        $prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('USD')->andReturn('ident_usd');

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $customer_adapter->shouldReceive('get')->with(7)->andReturn($customer);
        $amount_helper->shouldReceive('convertAmount')->with(12.34)->andReturn(1234);

        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $logger->shouldReceive('error')->never();
        $lock->shouldReceive('acquire')->with('uhf_cart:42', 30)->andReturn(true);
        $lock->shouldReceive('release')->with('uhf_cart:42')->once();
        // First call: Fix 2's pending-operation reconciliation check (none cached yet). Second
        // call: Fix 6's read-after-write check right after the set() below.
        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, 'op_123');
        $token_cache->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $token_cache->shouldReceive('set')->with('uhf_challenge_html:42', \Mockery::any(), \Mockery::any())->never();
        $token_cache->shouldReceive('set')->with($this->tokenCartKeyMatcher(), '42', 600)->once();
        $token_cache->shouldReceive('set')->with('uhf_cart_token:42', \Mockery::type('string'), 600)->once();
        $payment_service->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"op_123","execCode":"0000_SUCCESS"}',
            'https://acs.example/challenge',
            null,
            null
        ));

        $link->shouldReceive('getModuleLink')->with('payplug', 'unified', $this->tokenActionParams('return'), true)
            ->andReturn('https://shop.example/module/payplug/unified?action=return&id_cart=42');

        $action->dependencies = $dependencies;

        $result = $action->createAction([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
        ]);

        $this->assertSame([
            'result' => true,
            'redirect_url' => 'https://acs.example/challenge',
            'return_url' => 'https://acs.example/challenge',
        ], $result);
    }

    public function testCreateActionRejectsWhenLockIsAlreadyHeld()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->configClass = \Mockery::mock('ConfigClass');
        $dependencies->configClass->shouldReceive('isValidFeature')->with('feature_hosted_fields')->andReturn(true);
        $dependencies->shouldReceive('l')->andReturnUsing(function ($message) {
            return $message;
        });

        $plugin = \Mockery::mock('Plugin');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');
        $lock = \Mockery::mock('Lock');
        $token_cache = \Mockery::mock('TokenCache');
        $factory = \Mockery::mock('Factory');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');

        $context = (object) ['cart' => (object) ['id' => 42]];
        $cart = new class() {
            public $id = 42;
            public $id_currency = 2;
        };
        $currency = (object) ['iso_code' => 'USD'];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);

        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);

        $factory->shouldReceive('createLogger')->andReturn(\Mockery::mock('Logger'));
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->never();
        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null);
        $lock->shouldReceive('acquire')->with('uhf_cart:42', 30)->andReturn(false);
        $lock->shouldReceive('release')->never();

        $action->dependencies = $dependencies;

        $result = $action->createAction([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'Please wait, your previous request is still being processed.',
        ], $result);
    }

    public function testCreateActionReturnsErrorAndReleasesLockWhenCreatePaymentThrows()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andThrow(new \Exception('unified API down'));
        $mocks['logger']->shouldReceive('error')->once();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $result);
    }

    public function testCreateActionReturnsErrorWhenOperationIdIsMissingFromResponse()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            200,
            '{"execCode":"0000"}',
            null,
            null,
            null
        ));
        $mocks['logger']->shouldReceive('error')->once();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $result);
    }

    public function testCreateActionRequestsSavedCardWhenSaveCardAndCardholderAreGiven()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')
            ->once()
            ->withArgs(function ($hosted_field_dto) {
                return true === $hosted_field_dto->paymentMethod['saveFutureUsage']
                    && 'SUBSCRIPTION' === $hosted_field_dto->recurringMode
                    && 'Jane Doe' === $hosted_field_dto->paymentMethod['details']['fullName'];
            })
            ->andReturn(new PaymentOutput(201, '{"id":"op_123","execCode":"0000_SUCCESS"}', 'https://acs.example/challenge', null, null));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, 'op_123');
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with($this->tokenCartKeyMatcher(), '42', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with('uhf_cart_token:42', \Mockery::type('string'), 600)->once();

        $result = $action->createAction($this->defaultParams([
            'save_card' => 1,
            'cardholder' => 'Jane Doe',
        ]));

        $this->assertTrue($result['result']);
    }

    /**
     * Fix 3 (PRE-3626 review): everything from lock acquisition through to the final return must
     * now be wrapped so the lock is released exactly once on every exit path, including an
     * exception that has nothing to do with createPayment() itself (previously, only the
     * createPayment()-specific catch released the lock - anything thrown afterwards, e.g. a DB
     * error inside UpcTokenCache::set(), skipped every release() call and left the cart locked
     * out for the full 30s TTL).
     */
    public function testCreateActionReleasesLockViaFinallyWhenTokenCacheSetThrowsAfterPaymentCreation()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"op_123","execCode":"0000_SUCCESS"}',
            'https://acs.example/challenge',
            null,
            null
        ));
        $mocks['token_cache']->shouldReceive('set')
            ->with('uhf_pending_operation:42', 'op_123', 600)
            ->once()
            ->andThrow(new \Exception('cache db down'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cache db down');

        $action->createAction($this->defaultParams());
    }

    /**
     * Fix 6 (PRE-3626 review): UpcTokenCache::set() can't propagate a write failure through
     * ITokenCache's void return type, so createAction() does a cheap read-after-write check right
     * after caching the pending operation - if it doesn't round-trip, this must be treated as
     * fatal for the request (generic error, lock released) rather than redirecting the customer
     * toward a challenge/return flow that will find nothing cached.
     */
    public function testCreateActionReturnsGenericErrorAndReleasesLockWhenReadAfterWriteCheckFails()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"op_123","execCode":"0000_SUCCESS"}',
            'https://acs.example/challenge',
            null,
            null
        ));
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        // The write silently failed: both the pending-operation check and the read-after-write
        // check read back nothing.
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Failed to persist pending operation cache/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $result);
    }

    /**
     * Fix 2 (PRE-3626 review): when reconciling an already in-flight pending operation fails
     * (e.g. it genuinely expired/vanished server-side), createAction() must not hard-fail the
     * whole checkout over it - it falls through and creates a fresh payment instead.
     */
    public function testCreateActionCreatesNewPaymentWhenReconciliationGetOperationThrows()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_stale', 'op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_stale')->once()->andThrow(new \Exception('operation not found'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/getOperation failed/'));
        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"op_123","execCode":"0000_SUCCESS"}',
            'https://acs.example/challenge',
            null,
            null
        ));
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with($this->tokenCartKeyMatcher(), '42', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with('uhf_cart_token:42', \Mockery::type('string'), 600)->once();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertTrue($result['result']);
    }

    /**
     * Fix 2 (PRE-3626 review): a pending operation that has already resolved to a terminal
     * outcome server-side (e.g. the customer completed the 3DS challenge in the abandoned tab)
     * must be reconciled instead of creating a second, brand-new payment for the same cart -
     * this is what actually prevents the double payment. Reuses createFromOutcome()'s own
     * "order already exists" shortcut to avoid mocking a full validateOrder() flow, the same
     * trick returnActionTest's mockOrderCreatedBetweenChecks() uses.
     */
    public function testCreateActionReconcilesPaidPendingOperationInsteadOfCreatingANewPayment()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->configClass = \Mockery::mock('ConfigClass');
        $dependencies->configClass->shouldReceive('isValidFeature')->with('feature_hosted_fields')->andReturn(true);
        $dependencies->shouldReceive('l')->andReturnUsing(function ($message) {
            return $message;
        });

        $plugin = \Mockery::mock('Plugin');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');
        $amount_helper = \Mockery::mock('AmountHelper');
        $lock = \Mockery::mock('Lock');
        $token_cache = \Mockery::mock('TokenCache');
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $factory = \Mockery::mock('Factory');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');

        $context = (object) [
            'cart' => (object) ['id' => 42],
        ];
        $cart = new class() {
            public $id = 42;
            public $id_currency = 2;

            public function getOrderTotal($withTaxes, $type)
            {
                return 12.34;
            }
        };
        $currency = (object) ['iso_code' => 'USD'];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => $amount_helper]);

        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        // createAction()'s own pre-existing order_exists check (no order yet). The reconciliation
        // path's own "does the order already exist" check now lives behind the mocked order
        // creator below, so it no longer needs a second getIdByCartId()/get()/isCreated() wiring.
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $amount_helper->shouldReceive('convertAmount')->with(12.34)->andReturn(1234);

        $factory->shouldReceive('createLogger')->andReturn(\Mockery::mock('Logger'));
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);

        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_pending');
        $token_cache->shouldReceive('delete')->with('uhf_pending_operation:42')->once();
        $token_cache->shouldReceive('delete')->with('uhf_challenge_html:42')->once();

        $payment_service->shouldReceive('getOperation')->with('op_pending')->once()->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']),
        ]);
        $payment_service->shouldReceive('createPayment')->never();

        $lock->shouldReceive('acquire')->with('uhf_cart:42', 60)->andReturn(true);
        $lock->shouldReceive('release')->with('uhf_cart:42')->once();

        // UnifiedOrderAction now has its own dedicated test coverage (UnifiedOrderActionTest) -
        // this suite only needs to verify OperationAction reaches it with the right arguments and
        // relays whatever it returns, not re-exercise its internal order-creation/reconciliation
        // logic (order adapter, validator, context link, ...).
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_pending', '0000', PaymentOutcome::PAID, 1234)
            ->andReturn([
                'result' => true,
                'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            ]);

        $action->dependencies = $dependencies;

        $result = $action->createAction([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
        ]);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * Fix 2 (PRE-3626 review): a pending operation still awaiting its 3DS challenge must not
     * cause createAction() to create a second payment - the customer is instead sent back to the
     * existing challenge, so a page refresh resumes it instead of starting a duplicate one.
     */
    public function testCreateActionRedirectsToExistingChallengeWhenPendingOperationIsStillThreeDsPending()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->configClass = \Mockery::mock('ConfigClass');
        $dependencies->configClass->shouldReceive('isValidFeature')->with('feature_hosted_fields')->andReturn(true);
        $dependencies->shouldReceive('l')->andReturnUsing(function ($message) {
            return $message;
        });

        $plugin = \Mockery::mock('Plugin');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');
        $token_cache = \Mockery::mock('TokenCache');
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $factory = \Mockery::mock('Factory');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $link = \Mockery::mock('Link');

        $context = (object) [
            'cart' => (object) ['id' => 42],
            'link' => $link,
        ];
        $cart = new class() {
            public $id = 42;
            public $id_currency = 2;
        };
        $currency = (object) ['iso_code' => 'USD'];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);

        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);

        $factory->shouldReceive('createLogger')->andReturn(\Mockery::mock('Logger'));
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $factory->shouldReceive('createLock')->never();

        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_pending');
        $payment_service->shouldReceive('getOperation')->with('op_pending')->once()->andReturn([
            'body' => json_encode(['execCode' => '0001']),
        ]);
        $payment_service->shouldReceive('createPayment')->never();

        // Fix 2 (PRE-3626 review): reconcilePendingOperation() rebuilds the SAME challenge URL
        // using the SAME token originally minted by the createAction() call that's still pending,
        // resolved via the uhf_cart_token reverse mapping - not a fresh id_cart-based URL.
        $token_cache->shouldReceive('get')->with('uhf_cart_token:42')->andReturn('existing-token-abc');

        $link->shouldReceive('getModuleLink')->with('payplug', 'unified', ['action' => 'challenge', 'token' => 'existing-token-abc'], true)
            ->andReturn('https://shop.example/module/payplug/unified?action=challenge&token=existing-token-abc');

        $action->dependencies = $dependencies;

        $result = $action->createAction([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
        ]);

        $this->assertSame([
            'result' => true,
            'redirect_url' => 'https://shop.example/module/payplug/unified?action=challenge&token=existing-token-abc',
            'return_url' => 'https://shop.example/module/payplug/unified?action=challenge&token=existing-token-abc',
        ], $result);
    }

    /**
     * Common setup for the tests above: reaches OperationAction::createAction()'s try block
     * (lock acquired, currency/cart/order validated), letting each test configure only what
     * happens with the payment-creation call itself.
     *
     * @return array{0: OperationAction, 1: array<string, object>}
     */
    private function mockActionForPaymentCreation()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->configClass = \Mockery::mock('ConfigClass');
        $dependencies->configClass->shouldReceive('isValidFeature')->with('feature_hosted_fields')->andReturn(true);
        $dependencies->shouldReceive('l')->andReturnUsing(function ($message) {
            return $message;
        });

        $plugin = \Mockery::mock('Plugin');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $customer_adapter = \Mockery::mock('CustomerAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');
        $amount_helper = \Mockery::mock('AmountHelper');
        $lock = \Mockery::mock('Lock');
        $logger = \Mockery::mock('Logger');
        $token_cache = \Mockery::mock('TokenCache');
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $factory = \Mockery::mock('Factory');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $link = \Mockery::mock('Link');

        $context = (object) [
            'cart' => (object) ['id' => 42],
            'link' => $link,
        ];
        $cart = new class() {
            public $id = 42;
            public $id_currency = 2;
            public $id_customer = 7;

            public function getOrderTotal($withTaxes, $type)
            {
                return 12.34;
            }
        };
        $currency = (object) ['iso_code' => 'USD'];
        $customer = (object) ['email' => 'john@example.com'];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $dependencies->shouldReceive('getHelpers')->andReturn([
            'amount' => $amount_helper,
            'cookies' => \Mockery::mock('CookiesHelper'),
        ]);

        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCustomer')->andReturn($customer_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getTools')->andReturnSelf();
        $plugin->shouldReceive('tool')->with('getRemoteAddr')->andReturn('127.0.0.1');

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);
        $prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('USD')->andReturn('ident_usd');

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $customer_adapter->shouldReceive('get')->with(7)->andReturn($customer);
        $amount_helper->shouldReceive('convertAmount')->with(12.34)->andReturn(1234);

        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $lock->shouldReceive('acquire')->with('uhf_cart:42', 30)->andReturn(true);
        // Fix 2's pending-operation reconciliation check: no pending operation cached by default.
        // Tests that go on to cache one (via a redirectHtml/redirectUrl response) override this
        // with a second return value for Fix 6's read-after-write check.
        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null)->byDefault();

        $link->shouldReceive('getModuleLink')->with('payplug', 'unified', $this->tokenActionParams('return'), true)
            ->andReturn('https://shop.example/module/payplug/unified?action=return&id_cart=42');

        $action->dependencies = $dependencies;

        return [$action, [
            'logger' => $logger,
            'lock' => $lock,
            'token_cache' => $token_cache,
            'payment_service' => $payment_service,
        ]];
    }

    private function defaultParams(array $overrides = [])
    {
        return array_merge([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
        ], $overrides);
    }

    /**
     * Matches the getModuleLink() params array createAction() now builds for 'return'/'challenge'
     * URLs - a real, unpredictable bin2hex(random_bytes(32)) token replaces the old fixed id_cart,
     * so tests can only assert the action and that a non-empty string token is present.
     *
     * @param string $action
     */
    private function tokenActionParams($action)
    {
        return \Mockery::on(function ($params) use ($action) {
            return is_array($params)
                && isset($params['action'], $params['token'])
                && $action === $params['action']
                && is_string($params['token'])
                && '' !== $params['token'];
        });
    }

    /**
     * Matches a 'uhf_token_cart:<token>' cache key regardless of the actual (unpredictable) token
     * value.
     */
    private function tokenCartKeyMatcher()
    {
        return \Mockery::on(function ($key) {
            return is_string($key) && 0 === strpos($key, 'uhf_token_cart:');
        });
    }
}
