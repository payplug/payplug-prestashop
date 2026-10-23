<?php

namespace PayPlug\tests\actions\UnifiedOrderAction;

use PayPlug\src\actions\UnifiedOrderAction;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../stubs/Cart.php';

abstract class BaseUnifiedOrderAction extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * Builds a UnifiedOrderAction instance without running its real constructor (which would
     * otherwise build a real DependenciesClass when called with no argument, unavailable in this
     * unit-test environment), assigning the mocked $dependencies directly onto its public
     * property instead - same pattern UnifiedPaymentAction's own test suite already uses for that
     * class. (The constructor does accept an optional $dependencies now, but going through
     * newInstanceWithoutConstructor() here keeps every test in this folder uniform.).
     *
     * @param mixed $dependencies
     */
    protected function mockOrderAction($dependencies)
    {
        $order_action = (new \ReflectionClass(UnifiedOrderAction::class))->newInstanceWithoutConstructor();
        $order_action->dependencies = $dependencies;

        return $order_action;
    }

    /**
     * Common mocks for the "an order already exists for this cart" branch shared by several
     * tests (createFromOutcome()'s reconciliation path and existingOrderRedirect()), up to
     * (but not including) whatever each test's own scenario configures on top (the
     * payment-repository/order-state-mutator factory wiring for createFromOutcome() tests).
     *
     * @return array{0: object, 1: array<string, object>}
     */
    protected function mockDependenciesForExistingOrder()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $context_adapter = \Mockery::mock('ContextAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $order_class = \Mockery::mock('OrderClass');

        $existing_order = $this->mockOrder(12.34);
        $existing_order->secure_key = 'order_secure_key';
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?key=' . $params['key'] . '&id_order=' . $params['id_order'];
            }
        }];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $amount_helper = \Mockery::mock('AmountHelper');
        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => $amount_helper])->byDefault();
        $amount_helper->shouldReceive('convertAmount')->with(12.34, false, 'USD')->andReturn(1234)->byDefault();
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter)->byDefault();
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn((object) ['iso_code' => 'USD'])->byDefault();

        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context_adapter);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class)->byDefault();
        $configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false)->byDefault();
        $plugin->shouldReceive('getOrderClass')->andReturn($order_class)->byDefault();
        $order_class->shouldReceive('getOrderStates')->with(true)->andReturn(['error' => 5, 'paid' => 2, 'pending' => 1])->byDefault();

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(99);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($existing_order);
        $validator->shouldReceive('isCreated')->with($existing_order, 42)->andReturn(['result' => true]);
        $context_adapter->shouldReceive('get')->andReturn($context);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->id = 17;

        return [$dependencies, [
            'plugin' => $plugin,
            'module' => $module,
            'order_class' => $order_class,
            'configuration_class' => $configuration_class,
            'amount_helper' => $amount_helper,
            'existing_order' => $existing_order,
        ]];
    }

    /**
     * Common mocks for the "no order exists yet, proceed to validateOrder()" branch shared by
     * several tests, up to (but not including) the getIdByCartId()/validateOrder() calls
     * each test configures differently.
     *
     * @return array{0: object, 1: array<string, object>}
     */
    protected function mockDependenciesForOrderCreation()
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
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');

        $cart = new class() {
            public $id = 42;
            public $id_currency = 2;
            public $id_customer = 7;
            public $secure_key = 'cart_secure_key';

            public function getOrderTotal($withTaxes, $type)
            {
                return 12.34;
            }
        };

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => $amount_helper])->byDefault();

        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter)->byDefault();
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn((object) ['iso_code' => 'USD'])->byDefault();
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class);
        $plugin->shouldReceive('getOrderClass')->andReturn($order_class);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($this->mockOrder(12.34))->byDefault();
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(0);
        $order_class->shouldReceive('getOrderStates')->with(true)->andReturn(['pending' => 5, 'paid' => 2, 'error' => 8]);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $amount_helper->shouldReceive('convertAmount')->with(1234, true, 'USD')->andReturn(12.34);
        $amount_helper->shouldReceive('convertAmount')->with(12.34, false, 'USD')->andReturn(1234)->byDefault();

        // Shared factory/logger (used by the validateOrder()-throws logging path, and reused as-is
        // by tests exercising persistAndApplyOutcome() further down the happy path) - a single
        // 'getService' expectation here, matched by every test in this group, avoids ambiguous
        // duplicate expectations on the same mocked call.
        $module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createLogger')->andReturn($logger);

        return [$dependencies, [
            'plugin' => $plugin,
            'order_adapter' => $order_adapter,
            'module' => $module,
            'factory' => $factory,
            'logger' => $logger,
            'currency_adapter' => $currency_adapter,
            'amount_helper' => $amount_helper,
        ]];
    }

    /**
     * @param float $orders_total total of all the orders created from the cart
     * @param float|null $total_paid total of this order alone, when the cart was split
     *
     * @return object
     */
    protected function mockOrder($orders_total, $total_paid = null)
    {
        return new class($orders_total, null === $total_paid ? $orders_total : $total_paid) {
            public $id = 99;
            public $id_currency = 2;
            public $module = 'payplug';
            public $total_paid;
            private $orders_total;

            public function __construct($orders_total, $total_paid)
            {
                $this->orders_total = $orders_total;
                $this->total_paid = $total_paid;
            }

            public function getOrdersTotalPaid()
            {
                return $this->orders_total;
            }
        };
    }

    /**
     * @param array<string, object> $mocks
     */
    protected function mockConfirmationLink(array $mocks)
    {
        $context_adapter = \Mockery::mock('ContextAdapter');
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?id_order=' . $params['id_order'];
            }
        }];
        $mocks['plugin']->shouldReceive('getContext')->andReturn($context_adapter);
        $context_adapter->shouldReceive('get')->andReturn($context);
    }

    /**
     * @param array<string, object> $mocks
     * @param object|null $payment_repository
     *
     * @return object the OrderStateMutator mock
     */
    protected function mockPersistence(array $mocks, $payment_repository = null)
    {
        if (null === $payment_repository) {
            $payment_repository = \Mockery::mock('PaymentRepository');
            $payment_repository->shouldReceive('save')->once();
        }
        $state_mutator = \Mockery::mock('OrderStateMutator');
        $mocks['factory']->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $mocks['factory']->shouldReceive('createOrderStateMutator')->andReturn($state_mutator);

        return $state_mutator;
    }

    /**
     * @param mixed $dependencies
     * @param array<string, object> $mocks
     *
     * @return object the UnifiedAliasRepository mock
     */
    protected function mockAliasCollaborators($dependencies, array $mocks)
    {
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $alias_repository = \Mockery::mock('UnifiedAliasRepository');

        $mocks['plugin']->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn((object) ['iso_code' => 'USD']);
        $dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);
        $prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('usd')->andReturn('ident_usd');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.models.repositories.unified_alias')
            ->andReturn($alias_repository);

        return $alias_repository;
    }
}
