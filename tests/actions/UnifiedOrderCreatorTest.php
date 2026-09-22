<?php

namespace PayPlug\tests\actions;

use PayPlug\src\actions\UnifiedOrderCreator;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group unified_order_creator
 */
class UnifiedOrderCreatorTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testCreateFromOutcomePreservesSecureKeyWhenOrderAlreadyExists()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $context_adapter = \Mockery::mock('ContextAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');

        $existing_order = (object) [
            'id' => 99,
            'secure_key' => 'order_secure_key',
        ];
        $cart = (object) [
            'id' => 42,
            'secure_key' => 'cart_secure_key',
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
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context_adapter);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(99);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($existing_order);
        $validator->shouldReceive('isCreated')->with($existing_order, 42)->andReturn(['result' => true]);
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $context_adapter->shouldReceive('get')->andReturn($context);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->id = 17;

        $result = UnifiedOrderCreator::createFromOutcome(
            $dependencies,
            42,
            'op_123',
            '0000_SUCCESS',
            'PAID',
            1234
        );
        $this->assertTrue($result['result']);
        $this->assertStringContainsString('key=order_secure_key', $result['redirect_url']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenOutcomeIsFailed()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $validator = \Mockery::mock('OrderValidator');

        $cart = (object) ['id' => 42, 'secure_key' => 'cart_secure_key'];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);

        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);

        $result = UnifiedOrderCreator::createFromOutcome(
            $dependencies,
            42,
            'op_123',
            '0000_ERROR',
            PaymentOutcome::FAILED,
            1234
        );

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenValidateOrderThrows()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $mocks['module']->shouldReceive('validateOrder')->once()->andThrow(new \Exception('validation failed'));

        $result = UnifiedOrderCreator::createFromOutcome($dependencies, 42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenNewOrderIdCannotBeResolved()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 0);
        $mocks['module']->shouldReceive('validateOrder')->once();

        $result = UnifiedOrderCreator::createFromOutcome($dependencies, 42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenPersistingTheOperationThrows()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();

        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 99);
        $mocks['module']->shouldReceive('validateOrder')->once();

        $factory = \Mockery::mock('Factory');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('save')->once()->andThrow(new \Exception('db down'));

        $result = UnifiedOrderCreator::createFromOutcome($dependencies, 42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
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

        $result = UnifiedOrderCreator::createFromOutcome($dependencies, 42, 'op_123', '0000', PaymentOutcome::PAID, 1234);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
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
