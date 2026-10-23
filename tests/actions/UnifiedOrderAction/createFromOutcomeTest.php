<?php

namespace PayPlug\tests\actions\UnifiedOrderAction;

use PayPlug\src\utilities\helpers\AmountHelper;
use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group action
 * @group unified_order_action
 */
class createFromOutcomeTest extends BaseUnifiedOrderAction
{
    public function testCreateFromOutcomePersistsAndAppliesTheOutcomeWhenOrderAlreadyExists()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');

        // An existing order (e.g. one whose paid state could not be applied) still gets the
        // outcome persisted and applied.
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
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
        $this->assertTrue($result['outcome_applied']);
    }

    public function testCreateFromOutcomeAbandonsTheOrderWhenOutcomeIsFailedAndOrderAlreadyExists()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return 'op_123' === $operation_data->operationId && '99' === $operation_data->orderId && PaymentOutcome::FAILED === $operation_data->outcome;
        });
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::FAILED);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0002_TECHNICAL_ERROR', PaymentOutcome::FAILED, 1234);

        $this->assertFalse($result['result']);
        $this->assertTrue($result['persisted']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testCreateFromOutcomeRefusesToReconcileAnOrderNotCreatedByThisModule()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');

        $existing_order = (object) [
            'id' => 99,
            'secure_key' => 'order_secure_key',
            'module' => 'wirepayment',
        ];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);

        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(99);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($existing_order);
        $validator->shouldReceive('isCreated')->with($existing_order, 42)->andReturn(['result' => true]);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/refusing to reconcile/'));
        $factory->shouldReceive('createPaymentRepository')->never();
        $factory->shouldReceive('createOrderStateMutator')->never();

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testCreateFromOutcomeStillRedirectsToConfirmationWhenReconciliationPersistenceThrowsForPaidOutcome()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $logger = \Mockery::mock('Logger');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->andThrow(new \Exception('db down'));
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/db down/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000_SUCCESS', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
        $this->assertFalse($result['outcome_applied']);
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
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->andThrow(new \Exception('db down'));
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/db down/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0002_TECHNICAL_ERROR', PaymentOutcome::FAILED, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
        $this->assertFalse($result['outcome_applied']);
    }

    public function testReportsTheOutcomeAsNotAppliedWhenTheStateMutationRaisesAnError()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $logger = \Mockery::mock('Logger');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->andThrow(new \TypeError('bad state'));
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/bad state/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertFalse($result['outcome_applied']);
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
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/validation failed/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenValidateOrderRaisesAnError()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $mocks['module']->shouldReceive('validateOrder')->once()->andThrow(new \TypeError('bad argument'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/bad argument/'));

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

        $payment_repository = \Mockery::mock('PaymentRepository');
        $mocks['factory']->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->andThrow(new \Exception('db down'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/db down/'));

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

        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $mocks['factory']->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $mocks['factory']->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->andThrow(new \RuntimeException('state mutation failed'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/state mutation failed/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
        $this->assertFalse($result['outcome_applied']);
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

        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $mocks['factory']->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return 'op_123' === $operation_data->operationId && '99' === $operation_data->orderId;
        });
        $mocks['factory']->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testCreatesTheOrderInThePendingStateWhenThePaidAmountMatchesTheCartTotal()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $this->mockConfirmationLink($mocks);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 99);
        $mocks['module']->shouldReceive('validateOrder')->once()->with(42, 5, 12.34, 'payplug', null, ['transaction_id' => 'op_123'], 2, false, 'cart_secure_key');
        $state_mutator = $this->mockPersistence($mocks);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
    }

    /**
     * The cart changed during the 3DS challenge (total 12.34) while the payment captured the 15.00
     * requested at its creation. Like the Retail flow, the order is validated in the logable paid
     * state with the amount actually paid, so validateOrder() itself switches it to PS_OS_ERROR
     * and records the payment; the PAID outcome must then never move it to "Payment accepted".
     */
    public function testLetsPrestaShopFlagTheOrderWhenThePaidAmountDiffersFromTheCartTotal()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $this->mockConfirmationLink($mocks);
        $mocks['amount_helper']->shouldReceive('convertAmount')->with(1500, true, 'USD')->andReturn(15.0);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 99);
        $mocks['module']->shouldReceive('validateOrder')->once()->with(42, 2, 15.0, 'payplug', null, ['transaction_id' => 'op_123'], 2, false, 'cart_secure_key');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = $this->mockPersistence($mocks, $payment_repository);
        $payment_repository->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return PaymentOutcome::PAID === $operation_data->outcome && 1500 === $operation_data->amount && '99' === $operation_data->orderId;
        });
        $state_mutator->shouldReceive('apply')->with('99', PaymentOutcome::PAID)->never();
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::FAILED);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::on(function ($message) {
            return false !== strpos($message, 'manual review')
                && false !== strpos($message, 'order 99')
                && false !== strpos($message, 'op_123')
                && false !== strpos($message, '1234')
                && false !== strpos($message, '1500');
        }));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1500);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
        $this->assertTrue($result['outcome_applied']);
    }

    /**
     * A notification replaying the PAID outcome onto the order flagged by the return (or the
     * PAID notification of an order created pending) must not overwrite the payment error.
     */
    public function testNeverMovesAnExistingOrderToPaidWhenItsTotalDiffersFromThePaidAmount()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();
        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $logger = \Mockery::mock('Logger');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $payment_repository->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return PaymentOutcome::PAID === $operation_data->outcome && 1500 === $operation_data->amount;
        });
        $state_mutator->shouldReceive('apply')->with('99', PaymentOutcome::PAID)->never();
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::FAILED);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/manual review/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1500);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * A cart split into several orders (one per carrier): the payment covers all of them.
     */
    public function testComparesThePaidAmountWithTheTotalOfAllTheOrdersOfTheCart()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();
        $mocks['existing_order']->total_paid = 6.17;
        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $payment_repository->shouldReceive('save')->once();
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);

        $this->assertTrue($this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234)['result']);
    }

    public function testAppliesThePaidOutcomeAndLogsWhenTheOrderTotalCannotBeCompared()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();
        $mocks['amount_helper']->shouldReceive('convertAmount')->with(12.34, false, 'USD')->andThrow(new \Exception('unknown currency'));
        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $logger = \Mockery::mock('Logger');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $payment_repository->shouldReceive('save')->once();
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);
        $logger->shouldReceive('error')->once()->with(\Mockery::pattern('/unknown currency/'));

        $this->assertTrue($this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234)['result']);
    }

    public function testValidatesTheOrderWithTheAmountOfAZeroDecimalCurrencyUnscaled()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => new AmountHelper($dependencies)]);
        $mocks['currency_adapter']->shouldReceive('getCurrency')->with(2)->andReturn((object) ['iso_code' => 'JPY']);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 0);
        $validated_amount = null;
        $mocks['module']->shouldReceive('validateOrder')->once()->andReturnUsing(function ($id_cart, $state, $amount) use (&$validated_amount) {
            $validated_amount = $amount;
        });

        $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertSame(1234.0, $validated_amount);
    }

    public function testPersistsThePendingAliasOnPaidOutcomeWhenCreatingTheOrder()
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

        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $mocks['factory']->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $mocks['factory']->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);

        $alias_repository = $this->mockAliasCollaborators($dependencies, $mocks);
        $alias_repository->shouldReceive('saveIfAbsent')
            ->once()
            ->with(7, 'alias_abc', 'usd', 'ident_usd', 'visa', '0001', '12', '2029')
            ->andReturn(true);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(
            42,
            'op_123',
            '0000',
            PaymentOutcome::PAID,
            1234,
            ['alias_id' => 'alias_abc', 'brand' => 'visa'],
            ['last4' => '0001', 'exp_month' => '12', 'exp_year' => '2029']
        );

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testPersistsThePendingAliasOnPaidOutcomeWhenTheOrderAlreadyExists()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $cart_adapter = \Mockery::mock('CartAdapter');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::PAID);
        $mocks['plugin']->shouldReceive('getCart')->andReturn($cart_adapter);
        $cart_adapter->shouldReceive('get')->with(42)->andReturn((object) ['id' => 42, 'id_currency' => 2, 'id_customer' => 7]);

        $alias_repository = $this->mockAliasCollaborators($dependencies, $mocks);
        $alias_repository->shouldReceive('saveIfAbsent')
            ->once()
            ->with(7, 'alias_abc', 'usd', 'ident_usd', 'visa', '0001', '12', '2029')
            ->andReturn(true);

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(
            42,
            'op_123',
            '0000',
            PaymentOutcome::PAID,
            1234,
            ['alias_id' => 'alias_abc', 'brand' => 'visa'],
            ['last4' => '0001', 'exp_month' => '12', 'exp_year' => '2029']
        );

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testDoesNotPersistAnAliasWhenPendingAliasIsNull()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once();
        $mocks['module']->shouldReceive('getService')->with('payplug.models.repositories.unified_alias')->never();

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(42, 'op_123', '0000', PaymentOutcome::PAID, 1234, null, []);

        $this->assertTrue($result['result']);
    }

    public function testDoesNotPersistAnAliasOnFailedOutcome()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForExistingOrder();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');

        $mocks['module']->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $factory->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once()->with('99', PaymentOutcome::FAILED);
        $mocks['module']->shouldReceive('getService')->with('payplug.models.repositories.unified_alias')->never();

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(
            42,
            'op_123',
            '0002',
            PaymentOutcome::FAILED,
            1234,
            ['alias_id' => 'alias_abc', 'brand' => 'visa'],
            []
        );

        $this->assertFalse($result['result']);
    }

    /**
     * PRE-3557 rule: an order is created only for a paid payment. A cardholder back from 3DS
     * already knows its result, so a THREE_DS_PENDING outcome never creates one.
     */
    public function testCreatesNoOrderForAThreeDsPendingOutcome()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $mocks['module']->shouldReceive('validateOrder')->never();
        $mocks['factory']->shouldReceive('createPaymentRepository')->never();
        $mocks['factory']->shouldReceive('createOrderStateMutator')->never();
        $mocks['module']->shouldReceive('getService')->with('payplug.models.repositories.unified_alias')->never();

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(
            42,
            'op_123',
            '0001',
            PaymentOutcome::THREE_DS_PENDING,
            1234,
            ['alias_id' => 'alias_abc', 'brand' => 'visa'],
            []
        );

        $this->assertFalse($result['result']);
        $this->assertFalse($result['persisted']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    /**
     * @dataProvider aliasPersistenceFailureProvider
     */
    public function testKeepsTheConfirmationResultWhenAliasPersistenceFails(\Closure $configure_save)
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

        $payment_repository = \Mockery::mock('PaymentRepository');
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $mocks['factory']->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once();
        $mocks['factory']->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);
        $state_mutator->shouldReceive('apply')->once();

        $alias_repository = $this->mockAliasCollaborators($dependencies, $mocks);
        $configure_save($alias_repository->shouldReceive('saveIfAbsent')->once());
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/failed to persist alias alias_abc/'));

        $result = $this->mockOrderAction($dependencies)->createFromOutcome(
            42,
            'op_123',
            '0000',
            PaymentOutcome::PAID,
            1234,
            ['alias_id' => 'alias_abc', 'brand' => 'visa'],
            []
        );

        $this->assertTrue($result['result']);
        $this->assertTrue($result['persisted']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function aliasPersistenceFailureProvider()
    {
        return [
            'save throws' => [function ($expectation) {
                $expectation->andThrow(new \Exception('db down'));
            }],
            'save returns false' => [function ($expectation) {
                $expectation->andReturn(false);
            }],
        ];
    }
}
