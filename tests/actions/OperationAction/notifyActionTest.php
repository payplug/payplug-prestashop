<?php

// tests/actions/OperationAction/notifyActionTest.php

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
class notifyActionTest extends TestCase
{
    private const VALID_BODY = '{"id":"359fe258-8264-4a90-9a40-d16e1736058d","execCode":"0000","orderId":"6","amount":2900}';

    public function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        \Mockery::close();
    }

    public function testCreatesOrderAndMarksTreatedOnPaidOutcome()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('359fe258-8264-4a90-9a40-d16e1736058d');

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testMarksTreatedAndReturnsOkWhenOutcomeIsFailed()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $failed_body = '{"id":"op_failed","execCode":"9999","orderId":"6","amount":2900}';
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn($failed_body);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, 'op_failed', '9999', PaymentOutcome::FAILED, 2900, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
        ]);
        $mocks['payment_repository']->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return 'op_failed' === $operation_data->operationId && PaymentOutcome::FAILED === $operation_data->outcome;
        });
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('op_failed');

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testReturnsServerErrorAndDoesNotMarkTreatedWhenOrderCreationFailsForNonFailedOutcome()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);

        // UnifiedOrderAction's own validateOrder()-throws handling has its own dedicated
        // coverage in UnifiedOrderActionTest - this suite only needs the mocked order creator to
        // report failure the same way createFromOutcome() would for a PAID outcome.
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/createFromOutcome failed/'));

        $result = $action->notifyAction();

        $this->assertSame(500, $result['http_status']);
    }

    public function testReturnsBadRequestWhenPayloadIsMalformed()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn('not json');
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Invalid notification/'));
        $mocks['lock']->shouldReceive('acquire')->never();

        $result = $action->notifyAction();

        $this->assertSame(400, $result['http_status']);
    }

    public function testReturnsBadRequestWhenOrderIdIsInvalid()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $invalid_body = '{"id":"op_x","execCode":"0000","orderId":"not-a-number","amount":2900}';
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn($invalid_body);
        $mocks['payment_repository']->shouldReceive('isTreated')->never();
        $mocks['lock']->shouldReceive('acquire')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Invalid orderId/'));

        $result = $action->notifyAction();

        $this->assertSame(400, $result['http_status']);
    }

    public function testReturnsOkAndSkipsProcessingForThreeDsPending()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $pending_body = '{"id":"op_pending","execCode":"0001","orderId":"6","amount":2900}';
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn($pending_body);
        $mocks['payment_repository']->shouldReceive('isTreated')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['lock']->shouldReceive('acquire')->never();

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testReturnsOkWithoutReprocessingWhenAlreadyTreated()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $mocks['payment_repository']->shouldReceive('isTreated')->with('359fe258-8264-4a90-9a40-d16e1736058d')->andReturn(true);
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['lock']->shouldReceive('acquire')->never();

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testReturnsConflictWhenLockCannotBeAcquired()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        // 1 initial attempt + OperationAction::LOCK_RETRY_ATTEMPTS (2) retries = 3 total.
        $mocks['lock']->shouldReceive('acquire')->times(3)->with('uhf_cart:6', 60)->andReturn(false);
        $mocks['lock']->shouldReceive('release')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Could not acquire lock/'));

        $result = $action->notifyAction();

        $this->assertSame(409, $result['http_status']);
    }

    /**
     * Fix 4 (PRE-3626 review): the webhook payload has no independent second source of cart
     * identity to cross-check orderId against (orderId IS the cart id here), so only the amount
     * is cross-checked against the cart's own actual total - on a mismatch, notifyAction() must
     * not call createFromOutcome() at all.
     */
    public function testReturnsServerErrorAndDoesNotCreateOrderWhenAmountDoesNotMatchTheCartTotal()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        // VALID_BODY claims amount 2900; the cart's own actual total converts to something else.
        $this->mockCartForAmountCrossCheck($action->dependencies, 1500);
        $mocks['lock']->shouldReceive('acquire')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/amount mismatch/'));

        $result = $action->notifyAction();

        $this->assertSame(500, $result['http_status']);
    }

    public function testAcceptsMatchingAuthorizationHeaderWhenSecretIsConfigured()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer expected-secret';

        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $mocks['configuration_repository']->shouldReceive('get')->with('webhook_authorization_header')->andReturn('Bearer expected-secret');
        $mocks['logger']->shouldReceive('error');
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);

        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testRejectsMismatchingAuthorizationHeaderWhenSecretIsConfigured()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer wrong-secret';

        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $mocks['configuration_repository']->shouldReceive('get')->with('webhook_authorization_header')->andReturn('Bearer expected-secret');
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Invalid notification/'));
        $mocks['lock']->shouldReceive('acquire')->never();

        $result = $action->notifyAction();

        $this->assertSame(400, $result['http_status']);

        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testFallsBackToRedirectHttpAuthorizationHeader()
    {
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer expected-secret';

        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $mocks['configuration_repository']->shouldReceive('get')->with('webhook_authorization_header')->andReturn('Bearer expected-secret');
        $mocks['logger']->shouldReceive('error');
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);

        unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    /**
     * @return array{0: OperationAction, 1: array<string, object>}
     */
    private function mockActionForNotify()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $tools_adapter = \Mockery::mock('ToolsAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $lock = \Mockery::mock('Lock');
        $configuration_repository = \Mockery::mock('ConfigurationRepository');
        $order_action = \Mockery::mock('OrderAction');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getTools')->andReturn($tools_adapter);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createConfigurationRepository')->andReturn($configuration_repository);
        $configuration_repository->shouldReceive('get')->with('webhook_authorization_header')->andReturn(null)->byDefault();
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $lock->shouldReceive('acquire')->andReturn(true)->byDefault();
        $lock->shouldReceive('release')->byDefault();
        $payment_repository->shouldReceive('isTreated')->andReturn(false)->byDefault();

        // Fix 7: cache cleanup after a terminal reconciliation - not asserted by every test, only
        // the ones specifically covering it.
        $token_cache = \Mockery::mock('TokenCache');
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache)->byDefault();
        $token_cache->shouldReceive('delete')->byDefault();

        // UnifiedOrderAction now has its own dedicated test coverage (UnifiedOrderActionTest) -
        // this suite mocks it as an opaque collaborator instead of re-exercising its internal
        // order-creation logic (order adapter, validator, module validateOrder(), context link,
        // order state mutator, ...). errorUrl() is stubbed by default since it's reached directly
        // by OperationAction's own lock-acquisition-failure path (createOrderWithLock()), not only
        // through createFromOutcome()'s own mocked return value.
        $action->orderAction = $order_action;
        $order_action->shouldReceive('errorUrl')
            ->andReturn('index.php?controller=order&step=3&has_error=1&modulename=payplug')
            ->byDefault();

        $action->dependencies = $dependencies;

        return [$action, [
            'tools_adapter' => $tools_adapter,
            'logger' => $logger,
            'payment_repository' => $payment_repository,
            'lock' => $lock,
            'configuration_repository' => $configuration_repository,
            'module' => $module,
            'token_cache' => $token_cache,
            'order_action' => $order_action,
        ]];
    }

    /**
     * Fix 4 (PRE-3626 review): wires notifyAction()'s own amount cross-check
     * ($plugin->getCart()->get($id_cart), converted the same way createAction()/returnAction()
     * do) - OperationAction's own logic, unrelated to the (now mocked) order creator.
     *
     * @param mixed $dependencies
     */
    private function mockCartForAmountCrossCheck($dependencies, int $expected_cents)
    {
        $plugin = $dependencies->getPlugin();
        $cart_adapter = \Mockery::mock('CartAdapter');
        $amount_helper = \Mockery::mock('AmountHelper');
        $cart = new class() {
            public $id = 6;

            public function getOrderTotal($withTaxes, $type)
            {
                return 29.0;
            }
        };

        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => $amount_helper]);
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $cart_adapter->shouldReceive('get')->with(6)->andReturn($cart);
        $amount_helper->shouldReceive('convertAmount')->with(29.0)->andReturn($expected_cents);
    }

    /**
     * Stubs the order creator's createFromOutcome() for tests that drive notifyAction() through
     * to OperationAction::createOrderWithLock() - the mocked order creator is an opaque
     * collaborator here, so only the arguments OperationAction itself is responsible for
     * assembling (id_cart is always 6 in this suite; operation_id/exec_code/outcome/amount vary
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
            ->with(6, $operation_id, $exec_code, $outcome, $amount)
            ->andReturn($return);
    }
}
