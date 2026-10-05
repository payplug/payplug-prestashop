<?php

namespace PayPlug\tests\actions\OperationAction;

use PayPlug\src\actions\OperationAction;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../stubs/Cart.php';

/**
 * @group unit
 * @group action
 * @group operation_action
 */
class resyncPendingOrderTest extends TestCase
{
    private const PENDING_STATE = 5;
    private const PAID_STATE = 2;
    private const ERROR_STATE = 8;

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testMovesAPendingOrderWhoseOperationIsPaidToPaidThroughTheSharedOrderCreationPath()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $this->mockGetOperation($mocks, ['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']);
        $mocks['lock']->shouldReceive('acquire')->once()->with('uhf_cart:42', 60)->andReturn(true);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        $mocks['order_action']->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_123', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'confirmation', 'persisted' => true, 'outcome_applied' => true]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('op_123');
        $this->mockOrderStates($mocks, self::PENDING_STATE, self::PAID_STATE);

        $this->assertTrue($action->resyncPendingOrder(99));
    }

    public function testMovesAPendingOrderWhoseOperationFailedToTheErrorStateThroughTheSharedOrderCreationPath()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $this->mockGetOperation($mocks, ['execCode' => '4001', 'amount' => 1234, 'orderId' => '42']);
        $mocks['order_action']->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_123', '4001', PaymentOutcome::FAILED, 1234, null, [])
            ->andReturn(['result' => false, 'redirect_url' => 'error', 'persisted' => true, 'outcome_applied' => true]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('op_123');
        $this->mockOrderStates($mocks, self::PENDING_STATE, self::ERROR_STATE);

        $this->assertTrue($action->resyncPendingOrder(99));
    }

    public function testFallsBackToAnyOperationOfTheOrderWhenNoneIsRecordedPaid()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')->andReturn(null);
        $mocks['payment_repository']->shouldReceive('getByOrderId')->with('99')
            ->andReturn(new OperationData('op_legacy', '0001', PaymentOutcome::THREE_DS_PENDING, 1234, '99'));
        $this->mockGetOperation($mocks, ['execCode' => '0000', 'amount' => 1234, 'orderId' => '42'], 'op_legacy');
        $mocks['order_action']->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_legacy', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'confirmation', 'persisted' => true, 'outcome_applied' => true]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('op_legacy');
        $this->mockOrderStates($mocks, self::PENDING_STATE, self::PAID_STATE);

        $this->assertTrue($action->resyncPendingOrder(99));
    }

    public function testLeavesTheOrderAsIsWhileThePaymentIsStillThreeDsPending()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $this->mockGetOperation($mocks, ['execCode' => '0001', 'amount' => 1234, 'orderId' => '42']);
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['lock']->shouldReceive('acquire')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $this->mockOrderStates($mocks, self::PENDING_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testLogsAndLeavesTheOrderAsIsWhenGetOperationFails()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andThrow(new \Exception('unified API down'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/order 99.*unified API down/'));
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $this->mockOrderStates($mocks, self::PENDING_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testLogsAndLeavesTheOrderAsIsWhenTheOperationHasNoAmount()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $this->mockGetOperation($mocks, ['execCode' => '4001', 'orderId' => '42']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/missing amount.*op_123/'));
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $this->mockOrderStates($mocks, self::PENDING_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testLeavesTheOrderAsIsWhenThePaidAmountDiffersFromTheRequestedOne()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $mocks['payment_repository']->shouldReceive('getRequestedAmountByOperationId')->with('op_123')->andReturn(1500);
        $this->mockGetOperation($mocks, ['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/manual review/'));
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $this->mockOrderStates($mocks, self::PENDING_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testLogsAndDoesNotMarkTheOperationTreatedWhenTheOutcomeCouldNotBeApplied()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $this->mockGetOperation($mocks, ['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']);
        $mocks['order_action']->shouldReceive('createFromOutcome')->once()
            ->andReturn(['result' => true, 'redirect_url' => 'confirmation', 'persisted' => true, 'outcome_applied' => false]);
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/could not be applied to order 99/'));
        $this->mockOrderStates($mocks, self::PENDING_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testLeavesTheOrderAsIsWhenTheCartLockCannotBeAcquired()
    {
        [$action, $mocks] = $this->mockAction();
        $this->mockPaidOperationRow($mocks);
        $this->mockGetOperation($mocks, ['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']);
        $mocks['lock']->shouldReceive('acquire')->andReturn(false);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Could not acquire lock/'));
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $this->mockOrderStates($mocks, self::PENDING_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testNeverCallsTheApiForAnOrderThatIsNotPending()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->never();
        $this->mockOrderStates($mocks, self::PAID_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testNeverCallsTheApiForARetailOrderWithoutUnifiedOperation()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')->andReturn(null);
        $mocks['payment_repository']->shouldReceive('getByOrderId')->with('99')
            ->andThrow(new PaymentNotFoundException('No UPC operation for order "99".'));
        $mocks['payment_service']->shouldReceive('getOperation')->never();
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $this->mockOrderStates($mocks, self::PENDING_STATE);

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testNeverTouchesAnOrderOfAnotherModule()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['order_adapter']->shouldReceive('get')->with(99)->andReturn((object) [
            'id' => 99,
            'id_cart' => 42,
            'module' => 'ps_wirepayment',
            'current_state' => self::PENDING_STATE,
        ]);
        $mocks['payment_service']->shouldReceive('getOperation')->never();

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    public function testComparesWithTheTestModePendingStateInSandbox()
    {
        [$action, $mocks] = $this->mockAction('1');
        $mocks['order_class']->shouldReceive('getOrderStates')->with(false)
            ->andReturn(['pending' => 15, 'paid' => 12, 'error' => 18]);
        $mocks['order_adapter']->shouldReceive('get')->with(99)->andReturn($this->order(5));
        $mocks['payment_service']->shouldReceive('getOperation')->never();

        $this->assertFalse($action->resyncPendingOrder(99));
    }

    /**
     * @param array<string, object> $mocks
     */
    private function mockPaidOperationRow(array $mocks)
    {
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')
            ->andReturn(new OperationData('op_123', '0000', PaymentOutcome::PAID, 1234, '99'));
    }

    /**
     * @param array<string, object> $mocks
     * @param array<string, mixed> $body
     * @param string $operation_id
     */
    private function mockGetOperation(array $mocks, array $body, $operation_id = 'op_123')
    {
        $mocks['payment_service']->shouldReceive('getOperation')->once()->with($operation_id)->andReturn([
            'body' => json_encode($body),
        ]);
    }

    /**
     * @param array<string, object> $mocks
     * @param int $current_state state of the order when it is opened
     * @param int|null $state_after state read back after the resync
     */
    private function mockOrderStates(array $mocks, $current_state, $state_after = null)
    {
        $orders = [$this->order($current_state)];
        if (null !== $state_after) {
            $orders[] = $this->order($state_after);
        }
        $mocks['order_adapter']->shouldReceive('get')->with(99)->andReturn(...$orders);
    }

    private function order($current_state)
    {
        return (object) [
            'id' => 99,
            'id_cart' => 42,
            'module' => 'payplug',
            'current_state' => $current_state,
        ];
    }

    /**
     * @param string $sandbox_mode
     *
     * @return array{0: OperationAction, 1: array<string, object>}
     */
    private function mockAction($sandbox_mode = '0')
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();
        $action->lockRetryDelayUsec = 0;

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');
        $token_cache = \Mockery::mock('TokenCache');
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $lock = \Mockery::mock('Lock');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $order_class = \Mockery::mock('OrderClass');
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $amount_helper = \Mockery::mock('AmountHelper');
        $order_action = \Mockery::mock('UnifiedOrderAction');

        $cart = new class() {
            public $id = 42;
            public $id_currency = 2;

            public function getOrderTotal($withTaxes, $type)
            {
                return 12.34;
            }
        };

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => $amount_helper]);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getOrderClass')->andReturn($order_class);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class);
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory)
            ->byDefault();
        $configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn($sandbox_mode);
        $order_class->shouldReceive('getOrderStates')->with(true)
            ->andReturn(['pending' => self::PENDING_STATE, 'paid' => self::PAID_STATE, 'error' => self::ERROR_STATE])
            ->byDefault();
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn((object) ['iso_code' => 'USD']);
        $amount_helper->shouldReceive('convertAmount')->with(12.34, false, 'USD')->andReturn(1234);

        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('getRequestedAmountByOperationId')->andReturn(null)->byDefault();
        $lock->shouldReceive('acquire')->andReturn(true)->byDefault();
        $lock->shouldReceive('release')->byDefault();
        $token_cache->shouldReceive('delete')->byDefault();
        $token_cache->shouldReceive('get')->with(\Mockery::pattern('/^uhf_pending_alias:/'))->andReturn(null)->byDefault();

        $action->orderAction = $order_action;
        $order_action->shouldReceive('errorUrl')->andReturn('index.php?controller=order&step=3&has_error=1&modulename=payplug');
        $action->dependencies = $dependencies;

        return [$action, [
            'module' => $module,
            'logger' => $logger,
            'payment_service' => $payment_service,
            'payment_repository' => $payment_repository,
            'lock' => $lock,
            'order_adapter' => $order_adapter,
            'order_class' => $order_class,
            'order_action' => $order_action,
        ]];
    }
}
