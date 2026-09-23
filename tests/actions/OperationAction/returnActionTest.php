<?php

namespace PayPlug\tests\actions\OperationAction;

use PayPlug\src\actions\OperationAction;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../stubs/Cart.php';

/**
 * @group unit
 * @group action
 * @group operation_action
 */
class returnActionTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testReturnsErrorUrlWhenTokenIsMissing()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Invalid or expired token/'));

        $result = $action->returnAction([]);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenTokenIsUnknownOrExpired()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:bad-token')->andReturn(null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Invalid or expired token/'));

        $result = $action->returnAction(['token' => 'bad-token']);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenNoPendingOperationIsCached()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null);
        $mocks['logger']->shouldReceive('error')->once();

        $result = $action->returnAction(['token' => 'good-token']);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenGetOperationThrows()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andThrow(new \Exception('unified API down'));
        $mocks['logger']->shouldReceive('error')->once();

        $result = $action->returnAction(['token' => 'good-token']);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsExistingOrderConfirmationWithoutCallingGetOperation()
    {
        [$action, $mocks] = $this->mockActionForReturn();
        $this->mockOrderAlreadyExists($mocks);

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        // The order already exists at the very first (cheap, local) check - returnAction() must
        // short-circuit there and never reach the external getOperation() call at all.
        $mocks['payment_service']->shouldReceive('getOperation')->never();
        $mocks['logger']->shouldReceive('error')->never();

        $result = $action->returnAction(['token' => 'good-token']);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testDefaultsToFailedAndLogsWhenAmountIsMissingFromResponse()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andReturn([
            'body' => json_encode(['execCode' => '0000']),
        ]);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/missing amount field/'));
        // The missing amount forces the outcome to FAILED (amount defaults to 0) before reaching
        // the order creator - UnifiedOrderAction's own "no order to create for a failed payment"
        // branch has its own dedicated coverage in UnifiedOrderActionTest, so this suite only
        // asserts OperationAction relays whatever the (mocked) order creator returns.
        $this->mockCreateFromOutcome($mocks, 'op_123', '0000', PaymentOutcome::FAILED, 0, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
        ]);

        $result = $action->returnAction(['token' => 'good-token']);

        // The logged warning (asserted above) is what actually proves the missing-amount guard ran.
        $this->assertFalse($result['result']);
    }

    public function testAcquiresAndReleasesLockAroundOrderCreation()
    {
        [$action, $mocks] = $this->mockActionForReturn();
        $this->mockCreateFromOutcome($mocks, 'op_123', '0000', PaymentOutcome::PAID, 1234, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
        ]);

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']),
        ]);
        $mocks['lock']->shouldReceive('acquire')->once()->with('uhf_cart:42', 60)->andReturn(true);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        // Fix 7: a terminal reconciliation (success here) must clear the now-stale
        // pending-operation/challenge cache entries.
        $mocks['token_cache']->shouldReceive('delete')->once()->with('uhf_pending_operation:42');
        $mocks['token_cache']->shouldReceive('delete')->once()->with('uhf_challenge_html:42');

        $result = $action->returnAction(['token' => 'good-token']);

        $this->assertTrue($result['result']);
    }

    public function testReturnsErrorWhenLockCannotBeAcquiredAfterRetries()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']),
        ]);
        // 1 initial attempt + OperationAction::LOCK_RETRY_ATTEMPTS (2) retries = 3 total.
        $mocks['lock']->shouldReceive('acquire')->times(3)->with('uhf_cart:42', 60)->andReturn(false);
        $mocks['lock']->shouldReceive('release')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Could not acquire lock/'));

        $result = $action->returnAction(['token' => 'good-token']);

        // Must fail safe rather than proceed unlocked - see the comment on this branch in
        // OperationAction::returnAction() for why racing createFromOutcome() unlocked here would
        // risk creating a duplicate order.
        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    /**
     * Fix 4 (PRE-3626 review): an orderId mismatch between the getOperation() response and the
     * cart being returned to must be treated the same as a missing amount - forced to FAILED
     * rather than trusted, since this is "the only remaining protection" (per the UHF technical
     * design doc) in the absence of full signature verification.
     */
    public function testForcesFailedOutcomeWhenResponseOrderIdDoesNotMatchTheCart()
    {
        [$action, $mocks] = $this->mockActionForReturn();
        // The mismatch only forces the outcome to FAILED - the response's own (matching) amount
        // still reaches the order creator unchanged.
        $this->mockCreateFromOutcome($mocks, 'op_123', '0000', PaymentOutcome::FAILED, 1234, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
        ]);

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => 1234, 'orderId' => '999']),
        ]);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/orderId mismatch/'));

        $result = $action->returnAction(['token' => 'good-token']);

        // The logged mismatch (asserted above) is what actually proves the cross-check ran.
        $this->assertFalse($result['result']);
    }

    /**
     * Fix 4 (PRE-3626 review): same as above, for an amount mismatch - the cart's own actual
     * total, not the response's claimed amount, is what's trusted.
     */
    public function testForcesFailedOutcomeWhenResponseAmountDoesNotMatchTheCartTotal()
    {
        [$action, $mocks] = $this->mockActionForReturn();
        // The mismatch only forces the outcome to FAILED - the response's own (mismatching)
        // amount still reaches the order creator unchanged.
        $this->mockCreateFromOutcome($mocks, 'op_123', '0000', PaymentOutcome::FAILED, 999999, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
        ]);

        $mocks['token_cache']->shouldReceive('get')->with('uhf_token_cart:good-token')->andReturn('42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => 999999, 'orderId' => '42']),
        ]);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/amount mismatch/'));

        $result = $action->returnAction(['token' => 'good-token']);

        $this->assertFalse($result['result']);
    }

    private function mockDependencies()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->shouldReceive('l')->andReturnUsing(function ($message) {
            return $message;
        });

        return $dependencies;
    }

    /**
     * @return array{0: OperationAction, 1: array<string, object>}
     */
    private function mockActionForReturn()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = $this->mockDependencies();
        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');
        $token_cache = \Mockery::mock('TokenCache');
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $lock = \Mockery::mock('Lock');
        $cookie_helper = \Mockery::mock('CookieHelper');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $amount_helper = \Mockery::mock('AmountHelper');
        $order_action = \Mockery::mock('OrderAction');

        // Fix 4's cross-check baseline: a cart whose own total (once converted to cents) matches
        // the 1234 amount/orderId "42" used across the getOperation() response bodies below, so
        // tests exercising the success path don't also have to wire a mismatch.
        $cart = new class() {
            public $id = 42;

            public function getOrderTotal($withTaxes, $type)
            {
                return 12.34;
            }
        };

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getHelpers')->andReturn(['cookies' => $cookie_helper, 'amount' => $amount_helper])->byDefault();
        $cookie_helper->shouldReceive('setPaymentErrorsCookie')->byDefault();
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $lock->shouldReceive('acquire')->andReturn(true)->byDefault();
        $lock->shouldReceive('release')->byDefault();
        // Fix 7: cache cleanup after a terminal reconciliation - not asserted by every test, only
        // the ones specifically covering it.
        $token_cache->shouldReceive('delete')->byDefault();

        // UnifiedOrderAction now has its own dedicated test coverage (UnifiedOrderActionTest) -
        // this suite mocks it as an opaque collaborator OperationAction reads a result shape from,
        // instead of re-exercising its internal order-creation/reconciliation logic (order
        // adapter, validator, context link, ...). Default baseline: no order exists yet for this
        // cart. mockOrderAlreadyExists()/mockCreateFromOutcome() add overriding expectations on
        // this SAME mock for the tests that need something else.
        $action->orderAction = $order_action;
        $order_action->shouldReceive('errorUrl')
            ->andReturn('index.php?controller=order&step=3&has_error=1&modulename=payplug')
            ->byDefault();
        $order_action->shouldReceive('existingOrderRedirect')->with(42)->andReturn(null)->byDefault();

        // Fix 4's cross-check baseline (see $cart above).
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter)->byDefault();
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart)->byDefault();
        $amount_helper->shouldReceive('convertAmount')->with(12.34)->andReturn(1234)->byDefault();

        $action->dependencies = $dependencies;

        return [$action, [
            'logger' => $logger,
            'token_cache' => $token_cache,
            'payment_service' => $payment_service,
            'lock' => $lock,
            'plugin' => $plugin,
            'order_action' => $order_action,
        ]];
    }

    /**
     * Overrides the default "no order yet" baseline so the order already exists on every check -
     * exercises returnAction()'s early existingOrderRedirect() short-circuit.
     *
     * @param array<string, object> $mocks
     */
    private function mockOrderAlreadyExists(array $mocks)
    {
        $mocks['order_action']->shouldReceive('existingOrderRedirect')
            ->with(42)
            ->andReturn([
                'result' => true,
                'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            ]);
    }

    /**
     * Stubs the order creator's createFromOutcome() for tests that drive returnAction() through
     * to OperationAction::createOrderWithLock() - the mocked order creator is an opaque
     * collaborator here, so only the arguments OperationAction itself is responsible for
     * assembling (id_cart is always 42 in this suite; operation_id/exec_code/outcome/amount vary
     * per test) and the result shape the rest of the test needs are wired.
     *
     * @param array<string, object> $mocks
     * @param string $operation_id
     * @param string $exec_code
     * @param string $outcome
     * @param int $amount
     * @param array{result: bool, redirect_url: string} $return
     */
    private function mockCreateFromOutcome(array $mocks, $operation_id, $exec_code, $outcome, $amount, array $return)
    {
        $mocks['order_action']->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, $operation_id, $exec_code, $outcome, $amount)
            ->andReturn($return);
    }
}
