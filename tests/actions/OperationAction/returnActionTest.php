<?php

namespace PayPlug\tests\actions\OperationAction;

use PayPlug\src\actions\OperationAction;
use PHPUnit\Framework\TestCase;

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

    public function testReturnsErrorUrlWhenIdCartIsInvalid()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();
        $action->dependencies = $this->mockDependencies();

        $result = $action->returnAction(['id_cart' => 0]);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenNoPendingOperationIsCached()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null);
        $mocks['logger']->shouldReceive('error')->once();

        $result = $action->returnAction(['id_cart' => 42]);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReturnsErrorUrlWhenGetOperationThrows()
    {
        [$action, $mocks] = $this->mockActionForReturn();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andThrow(new \Exception('unified API down'));
        $mocks['logger']->shouldReceive('error')->once();

        $result = $action->returnAction(['id_cart' => 42]);

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    public function testReconcilesTheOutcomeAndReturnsTheExistingOrderConfirmation()
    {
        [$action, $mocks] = $this->mockActionForReturn();
        [$order_adapter, $cart_adapter, $context] = $this->mockOrderAlreadyExists($action->dependencies);

        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => 1234]),
        ]);
        $mocks['logger']->shouldReceive('error')->never();

        $result = $action->returnAction(['id_cart' => 42]);

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testDefaultsToFailedAndLogsWhenAmountIsMissingFromResponse()
    {
        [$action, $mocks] = $this->mockActionForReturn();
        $this->mockOrderAlreadyExists($action->dependencies);

        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_123');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->andReturn([
            'body' => json_encode(['execCode' => '0000']),
        ]);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/missing amount field/'));

        $result = $action->returnAction(['id_cart' => 42]);

        // The order-already-exists branch short-circuits before the outcome/amount are used,
        // so forcing outcome to FAILED here doesn't change the result - only the logged warning
        // (asserted above) proves the missing-amount guard ran.
        $this->assertTrue($result['result']);
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

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);

        $action->dependencies = $dependencies;

        return [$action, [
            'logger' => $logger,
            'token_cache' => $token_cache,
            'payment_service' => $payment_service,
        ]];
    }

    /**
     * Wires UnifiedOrderCreator::createFromOutcome()'s order-already-exists branch, the
     * cheapest way to exercise returnAction()'s delegation to it without re-testing every
     * order-creation branch already covered by UnifiedOrderCreatorTest.
     *
     * @param mixed $dependencies
     *
     * @return array{0: object, 1: object, 2: object}
     */
    private function mockOrderAlreadyExists($dependencies)
    {
        $plugin = $dependencies->getPlugin();
        $order_adapter = \Mockery::mock('OrderAdapter');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $context_adapter = \Mockery::mock('ContextAdapter');
        $validator = \Mockery::mock('OrderValidator');

        $existing_order = (object) ['id' => 99, 'secure_key' => 'order_secure_key'];
        $cart = (object) ['id' => 42, 'secure_key' => 'cart_secure_key'];
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?id_order=' . $params['id_order'];
            }
        }];

        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(99);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($existing_order);
        $validator->shouldReceive('isCreated')->with($existing_order, 42)->andReturn(['result' => true]);
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $context_adapter->shouldReceive('get')->andReturn($context);

        return [$order_adapter, $cart_adapter, $context];
    }
}
