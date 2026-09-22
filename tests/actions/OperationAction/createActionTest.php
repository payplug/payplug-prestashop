<?php

namespace PayPlug\tests\actions\OperationAction;

use PayPlug\src\actions\OperationAction;
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
        $token_cache->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $token_cache->shouldReceive('set')->with('uhf_challenge_html:42', \Mockery::any(), \Mockery::any())->never();
        $payment_service->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"op_123","execCode":"0000_SUCCESS"}',
            'https://acs.example/challenge',
            null,
            null
        ));

        $link->shouldReceive('getModuleLink')->with('payplug', 'unified', ['action' => 'return', 'id_cart' => 42], true)
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
        $factory->shouldReceive('create')->never();
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
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();

        $result = $action->createAction($this->defaultParams([
            'save_card' => 1,
            'cardholder' => 'Jane Doe',
        ]));

        $this->assertTrue($result['result']);
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

        $link->shouldReceive('getModuleLink')->with('payplug', 'unified', ['action' => 'return', 'id_cart' => 42], true)
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
}
