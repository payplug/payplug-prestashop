<?php

namespace PayPlug\tests\actions;

use PayPlug\src\actions\UnifiedOrderAction;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group unified_order_action
 */
class UnifiedOrderActionTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testCreateFromOutcomePersistsAndAppliesTheOutcomeWhenOrderAlreadyExists()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');

        // This is the fix under test: previously createFromOutcome() short-circuited on an
        // existing order WITHOUT ever calling save()/apply() again, so an order created early
        // (e.g. by returnAction() while a payment was still THREE_DS_PENDING) never got the real,
        // final outcome recorded when the confirming notifyAction() webhook arrived afterward.
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return 'op_123' === $operation_data->operationId && '99' === $operation_data->orderId && PaymentOutcome::PAID === $operation_data->outcome;
        });
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000_SUCCESS', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('key=order_secure_key', $result['redirect_url']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * The third of the three exec_code outcomes an existing pending order can be reconciled
     * against (see the "three outcomes" comment in createFromOutcome()): SUCCESS_EXEC_CODE
     * ('0000') updates it to paid (tested above), PENDING_THREE_DS_EXEC_CODE ('0001') never
     * reaches this method for an existing order (filtered upstream, or - for returnAction() -
     * never reaches this branch to begin with), and - this test - any other exec_code is
     * PaymentOutcome::FAILED, which must still update the order (to the 'error'/abandoned
     * bucket), not silently leave it stuck pending.
     */
    public function testCreateFromOutcomeAbandonsTheOrderWhenOutcomeIsFailedAndOrderAlreadyExists()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return 'op_123' === $operation_data->operationId && '99' === $operation_data->orderId && PaymentOutcome::FAILED === $operation_data->outcome;
        });
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::FAILED);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0002_TECHNICAL_ERROR', PaymentOutcome::FAILED, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testCreateFromOutcomeStillRedirectsToConfirmationWhenReconciliationPersistenceThrowsForPaidOutcome()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $logger = \Mockery::mock('Logger');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->andThrow(new \Exception('db down'));
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/db down/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000_SUCCESS', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * Counterpart of the test above: when the outcome being reconciled is FAILED (not PAID),
     * there is no successful charge to protect the customer's view of - if persisting/applying
     * the failure itself throws, createFromOutcome() must report failure rather than silently
     * confirming a payment that didn't happen.
     */
    public function testReturnsErrorUrlWhenReconciliationPersistenceThrowsForFailedOutcome()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $logger = \Mockery::mock('Logger');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->andThrow(new \Exception('db down'));
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/db down/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0002_TECHNICAL_ERROR', PaymentOutcome::FAILED, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testExistingOrderRedirectReturnsConfirmationUrlWhenOrderAlreadyExists()
    {
        [$dependencies] = $this->mockDependenciesForExistingOrder();

        $result = $this->mockOrderAction($dependencies)->existingOrderRedirect(42);

        $this->assertNotNull($result);
        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testExistingOrderRedirectReturnsNullWhenNoOrderExistsYet()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $validator = \Mockery::mock('OrderValidator');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);

        $this->assertNull($this->mockOrderAction($dependencies)->existingOrderRedirect(42));
    }

    public function testReturnsErrorUrlWhenOutcomeIsFailed()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $validator = \Mockery::mock('OrderValidator');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);

        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000_ERROR', PaymentOutcome::FAILED, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenValidateOrderThrows()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $mocks['module']->shouldReceive('validateOrder')->once()->andThrow(new \Exception('validation failed'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenNewOrderIdCannotBeResolved()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 0);
        $mocks['module']->shouldReceive('validateOrder')->once();

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    /**
     * Fix 5 (PRE-3626 review): by the time save()/apply() run, $module->validateOrder() already
     * succeeded - the customer WAS charged and DOES have a real order. Sending them to the
     * generic error page over a subsequent persistence failure would be wrong; the order genuinely
     * exists, so createFromOutcome() must still redirect to its confirmation (loudly logging the
     * failure instead, for ops to reconcile afterward).
     */
    public function testReturnsConfirmationAndLogsWhenPersistingTheOperationThrows()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $context_adapter = \Mockery::mock('ContextAdapter');
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?id_order=' . $params['id_order'];
            }
        }];
        $mocks['plugin']->shouldReceive('getContext')->andReturn($context_adapter);
        $context_adapter->shouldReceive('get')->andReturn($context);

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 99);
        $mocks['module']->shouldReceive('validateOrder')->once();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $logger = \Mockery::mock('Logger');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->andThrow(new \Exception('db down'));
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/db down/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * Same guarantee as above (Fix 5) when it's the order-state mutation, not the persistence
     * save(), that fails after validateOrder() already succeeded.
     */
    public function testReturnsConfirmationAndLogsWhenApplyingTheOrderStateThrows()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $context_adapter = \Mockery::mock('ContextAdapter');
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?id_order=' . $params['id_order'];
            }
        }];
        $mocks['plugin']->shouldReceive('getContext')->andReturn($context_adapter);
        $context_adapter->shouldReceive('get')->andReturn($context);

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 99);
        $mocks['module']->shouldReceive('validateOrder')->once();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $logger = \Mockery::mock('Logger');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->andThrow(new \Exception('state mutation failed'));
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/state mutation failed/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testCreatesTheOrderAndPersistsTheOperationOnSuccess()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $context_adapter = \Mockery::mock('ContextAdapter');
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?id_order=' . $params['id_order'];
            }
        }];
        $mocks['plugin']->shouldReceive('getContext')->andReturn($context_adapter);
        $context_adapter->shouldReceive('get')->andReturn($context);

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 99);
        $mocks['module']->shouldReceive('validateOrder')->once();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return 'op_123' === $operation_data->operationId && '99' === $operation_data->orderId;
        });
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * Builds a UnifiedOrderAction instance without running its real constructor (which would
     * otherwise build a real DependenciesClass when called with no argument, unavailable in this
     * unit-test environment), assigning the mocked $dependencies directly onto its public
     * property instead - same pattern OperationAction's own test suite already uses for that
     * class. (The constructor does accept an optional $dependencies now, but going through
     * newInstanceWithoutConstructor() here keeps every test in this file uniform.).
     *
     * @param mixed $dependencies
     */
    private function mockOrderAction($dependencies)
    {
        $order_action = (new \ReflectionClass(UnifiedOrderAction::class))->newInstanceWithoutConstructor();
        $order_action->dependencies = $dependencies;

        return $order_action;
    }

    /**
     * Common mocks for the "an order already exists for this cart" branch shared by several
     * tests above (createFromOutcome()'s reconciliation path and existingOrderRedirect()), up to
     * (but not including) whatever each test's own scenario configures on top (the
     * payment-repository/order-state-mutator factory wiring for createFromOutcome() tests).
     *
     * @return array{0: object, 1: array<string, object>}
     */
    private function mockDependenciesForExistingOrder()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $context_adapter = \Mockery::mock('ContextAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');

        $existing_order = (object) [
            'id' => 99,
            'secure_key' => 'order_secure_key',
        ];
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?key=' . $params['key'] . '&id_order=' . $params['id_order'];
            }
        }];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);

        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context_adapter);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(99);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($existing_order);
        $validator->shouldReceive('isCreated')->with($existing_order, 42)->andReturn(['result' => true]);
        $context_adapter->shouldReceive('get')->andReturn($context);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->id = 17;

        return [$dependencies, [
            'plugin' => $plugin,
            'module' => $module,
        ]];
    }

    /**
     * Common mocks for the "no order exists yet, proceed to validateOrder()" branch shared by
     * several tests above, up to (but not including) the getIdByCartId()/validateOrder() calls
     * each test configures differently.
     *
     * @return array{0: object, 1: array<string, object>}
     */
    private function mockDependenciesForOrderCreation()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $validator = \Mockery::mock('OrderValidator');
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $order_class = \Mockery::mock('OrderClass');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $amount_helper = \Mockery::mock('AmountHelper');

        $cart = (object) ['id' => 42, 'id_currency' => 2, 'secure_key' => 'cart_secure_key'];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => $amount_helper]);

        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class);
        $plugin->shouldReceive('getOrderClass')->andReturn($order_class);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(0);
        $order_class->shouldReceive('getOrderStates')->with(true)->andReturn(['pending' => 5]);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $amount_helper->shouldReceive('convertAmount')->with(1234, true)->andReturn(12.34);

        return [$dependencies, [
            'plugin' => $plugin,
            'order_adapter' => $order_adapter,
            'module' => $module,
        ]];
    }
}
