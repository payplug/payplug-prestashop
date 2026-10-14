<?php

namespace PayPlug\tests\actions\UnifiedPaymentAction;

use PayPlug\src\actions\UnifiedPaymentAction;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Dto\PaymentDto;
use PayplugUnifiedCore\Exceptions\ApiException;
use PayplugUnifiedCore\Output\PaymentOutput;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../stubs/Cart.php';

/**
 * @group unit
 * @group action
 * @group unified_payment_action
 */
class createActionTest extends TestCase
{
    private const STATIC_TOKEN = 'static_token_123';

    public function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    public function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        \Mockery::close();
    }

    public function testCreateActionCachesPendingOperationWhenUnifiedApiReturnsRedirectUrlOnly()
    {
        $action = (new \ReflectionClass(UnifiedPaymentAction::class))->newInstanceWithoutConstructor();

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
        $plugin->shouldReceive('tool')->with('getToken', false)->andReturn(self::STATIC_TOKEN);
        $plugin->shouldReceive('tool')->with('getRemoteAddr')->andReturn('127.0.0.1');

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.unified.payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);
        $prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('USD')->andReturn('ident_usd');

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $customer_adapter->shouldReceive('get')->with(7)->andReturn($customer);
        $amount_helper->shouldReceive('convertAmount')->with(12.34, false, 'USD')->andReturn(1234);

        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $payment_repository = \Mockery::mock('PaymentRepository');
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('bindPaymentId')->andReturn(true);
        $payment_repository->shouldReceive('bindRequestedAmount')->andReturn(true)->byDefault();
        $logger->shouldReceive('error')->never();
        $lock->shouldReceive('acquire')->with('uhf_cart:42', 60)->andReturn(true);
        $lock->shouldReceive('release')->with('uhf_cart:42')->once();
        // First call: Fix 2's pending-operation reconciliation check (none cached yet). Second
        // call: Fix 6's read-after-write check right after the set() below.
        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null, 'op_123');
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
        $link->shouldReceive('getModuleLink')->with('payplug', 'notify', [], true)
            ->andReturn('https://shop.example/module/payplug/notify');

        $action->dependencies = $dependencies;

        $result = $action->createAction([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
            'static_token' => self::STATIC_TOKEN,
        ]);

        $this->assertSame([
            'result' => true,
            'redirect_url' => 'https://acs.example/challenge',
            'return_url' => 'https://acs.example/challenge',
        ], $result);
    }

    public function testCreateActionRejectsWhenLockIsAlreadyHeld()
    {
        $action = (new \ReflectionClass(UnifiedPaymentAction::class))->newInstanceWithoutConstructor();

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
        $plugin->shouldReceive('getTools')->andReturnSelf();
        $plugin->shouldReceive('tool')->with('getToken', false)->andReturn(self::STATIC_TOKEN);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.unified.payment_service_factory')->andReturn($factory);

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
        $lock->shouldReceive('acquire')->with('uhf_cart:42', 60)->andReturn(false);
        $lock->shouldReceive('release')->never();

        $action->dependencies = $dependencies;

        $result = $action->createAction([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
            'static_token' => self::STATIC_TOKEN,
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'Please wait, your previous request is still being processed.',
        ], $result);
    }

    /**
     * @dataProvider untrustedCreateRequestProvider
     *
     * @param string $request_method
     * @param array<string, mixed> $params
     */
    public function testCreateActionRefusesAnUntrustedRequestBeforeAnyApiCall($request_method, array $params)
    {
        [$action, $mocks] = $this->mockActionForAliasPayment();
        $_SERVER['REQUEST_METHOD'] = $request_method;
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('acquire')->never();
        $mocks['token_cache']->shouldReceive('get')->never();
        $mocks['alias_repository']->shouldReceive('findUsable')->never();

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($params));
    }

    public function untrustedCreateRequestProvider()
    {
        $card = [
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
        ];
        $alias = ['id_payplug_alias' => '1', 'id_cart' => 42];

        yield 'card, missing token' => ['POST', $card];

        yield 'card, wrong token' => ['POST', $card + ['static_token' => 'forged']];

        yield 'card, non-string token' => ['POST', $card + ['static_token' => ['static_token_123']]];

        yield 'card, GET with a valid token' => ['GET', $card + ['static_token' => self::STATIC_TOKEN]];

        yield 'card, no request method' => ['', $card + ['static_token' => self::STATIC_TOKEN]];

        yield 'alias, missing token' => ['POST', $alias];

        yield 'alias, wrong token' => ['POST', $alias + ['static_token' => 'forged']];

        yield 'alias, GET with a valid token' => ['GET', $alias + ['static_token' => self::STATIC_TOKEN]];
    }

    public function testCreateActionRefusesAPaymentWhenAConcurrentRequestCreatedTheOrderBeforeTheLock()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order = (object) ['id' => 99, 'module' => 'payplug', 'current_state' => 2];
        $order_class = \Mockery::mock('OrderClass');
        $mocks['plugin']->shouldReceive('getOrderClass')->andReturn($order_class);
        $order_class->shouldReceive('getOrderStates')->with(true)->andReturn(['error' => 8, 'paid' => 2]);
        $mocks['configuration_class']->shouldReceive('getValue')->with('sandbox_mode')->andReturn('0');
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(42)->andReturn(0, 99);
        $mocks['order_adapter']->shouldReceive('get')->with(99)->andReturn($order);
        $mocks['validator']->shouldReceive('isCreated')->with($order, 42)->andReturn(['result' => true]);
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $result);
    }

    public function testCreateActionResumesTheChallengeOfAConcurrentRequestThatCachedItsOperationBeforeTheLock()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, 'op_concurrent');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_cart_token:42')->andReturn('concurrent-token');
        $mocks['payment_service']->shouldReceive('getOperation')->once()->with('op_concurrent')
            ->andReturn(['body' => json_encode(['execCode' => '0001'])]);
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        $mocks['link']->shouldReceive('getModuleLink')
            ->with('payplug', 'unified', ['action' => 'challenge', 'token' => 'concurrent-token'], true)
            ->andReturn('https://shop.example/module/payplug/unified?action=challenge&token=concurrent-token');

        $result = $action->createAction($this->defaultParams());

        $this->assertTrue($result['result']);
        $this->assertSame('https://shop.example/module/payplug/unified?action=challenge&token=concurrent-token', $result['redirect_url']);
    }

    public function testCreateActionAsksToWaitWhenTheConcurrentOperationFoundAfterTheLockCannotBeReconciled()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, 'op_concurrent');
        $mocks['payment_service']->shouldReceive('getOperation')->once()->with('op_concurrent')
            ->andThrow(new \Exception('unified API down'));
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/getOperation failed/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertSame([
            'result' => false,
            'message' => 'Please wait, your previous request is still being processed.',
        ], $result);
    }

    public function testCreateActionReleasesTheLockWhenTheCheckAfterTheLockThrows()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')
            ->andReturnUsing(function () {
                static $calls = 0;
                if (++$calls > 1) {
                    throw new \RuntimeException('db down');
                }

                return null;
            });
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $this->expectException(\RuntimeException::class);

        $action->createAction($this->defaultParams());
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

    /**
     * @dataProvider uncertainCreatePaymentFailureProvider
     *
     * @param int $status
     */
    public function testCreateActionAsksToCheckWithTheBankWhenThePaymentOutcomeIsUnknown($status)
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()
            ->andThrow(new ApiException('Unified API payment creation request failed with HTTP status ' . $status . '.', $status));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/outcome unknown for cart 42.*HTTP status ' . $status . '/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $this->assertSame([
            'result' => false,
            'message' => 'We could not confirm your payment. Please check with your bank before trying again.',
        ], $action->createAction($this->defaultParams()));
    }

    public function uncertainCreatePaymentFailureProvider()
    {
        yield 'timeout or connection failure' => [0];

        yield 'request timeout' => [408];

        yield 'conflict' => [409];

        yield 'server error' => [500];

        yield 'bad gateway' => [502];

        yield 'service unavailable' => [503];
    }

    /**
     * @dataProvider definiteCreatePaymentFailureProvider
     *
     * @param int $status
     */
    public function testCreateActionTellsTheCardWasNotChargedWhenThePaymentWasRefused($status)
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()
            ->andThrow(new ApiException('Unified API payment creation request failed with HTTP status ' . $status . '.', $status));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/createPayment failed for cart 42/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($this->defaultParams()));
    }

    public function definiteCreatePaymentFailureProvider()
    {
        yield 'bad request' => [400];

        yield 'unauthorized' => [401];

        yield 'not found' => [404];

        yield 'unprocessable' => [422];
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
                    && 'ONE_CLICK' === $hosted_field_dto->recurringMode
                    && 'Jane Doe' === $hosted_field_dto->paymentMethod['details']['fullName'];
            })
            ->andReturn(new PaymentOutput(201, '{"id":"op_123","execCode":"0000_SUCCESS"}', 'https://acs.example/challenge', null, null));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null, 'op_123');
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with($this->tokenCartKeyMatcher(), '42', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with('uhf_cart_token:42', \Mockery::type('string'), 600)->once();
        // No aliasId yet: the opt-in is still cached, the alias id is resolved on the return/notification.
        $mocks['token_cache']->shouldReceive('set')
            ->with('uhf_pending_alias:op_123', '{"alias_id":"","brand":"visa"}', 86400)
            ->once();
        $mocks['logger']->shouldReceive('error')->never();

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
     * error inside UnifiedTokenCache::set(), skipped every release() call and left the cart locked
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
     * PRE-3627: the payment-creation response carries the payment's own "id" (the key the refund
     * endpoint expects) next to "operationIds[0]" (the operation key used everywhere else). The
     * payment id must be bound to the operation as soon as the payment exists, before any order.
     */
    public function testCreateActionBindsThePaymentIdToTheOperationRightAfterPaymentCreation()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"pay_1","operationIds":["op_1"],"execCode":"0001"}',
            'https://acs.example/challenge',
            null,
            null
        ));
        $mocks['payment_repository']->shouldReceive('bindPaymentId')->once()->with('op_1', 'pay_1', 42, 1234)->andReturn(true);
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_1', 600)->once();
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Failed to persist pending operation cache/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertFalse($result['result']);
    }

    /**
     * notificationUrl is part of the payload the UHF technical doc specifies (the platform itself
     * routes notifications to the realm's Receiver): sent like the Woo/Sylius modules do.
     */
    public function testCreateActionSendsTheNotifyControllerAsNotificationUrl()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['link']->shouldReceive('getModuleLink')->with('payplug', 'notify', [], true)
            ->andReturn('https://shop.example/module/payplug/notify');
        $mocks['payment_service']->shouldReceive('createPayment')->once()
            ->withArgs(function ($dto) {
                $body = $dto->createPayloadBody();

                return isset($body['notificationUrl']) && 'https://shop.example/module/payplug/notify' === $body['notificationUrl'];
            })
            ->andReturn(new PaymentOutput(201, '{"id":"pay_1","operationIds":["op_1"],"execCode":"0001"}', 'https://acs.example/challenge', null, null));
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_1', 600)->once();
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Failed to persist pending operation cache/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertFalse($result['result']);
    }

    public function testCreateActionKeepsGoingAndLogsWhenThePaymentIdCannotBeBound()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"pay_1","operationIds":["op_1"],"execCode":"0001"}',
            'https://acs.example/challenge',
            null,
            null
        ));
        $mocks['payment_repository']->shouldReceive('bindPaymentId')->once()->andThrow(new \Exception('db down'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/payment id pay_1 .*op_1.* not recorded.*refunds/'));
        // The payment itself still goes on to its challenge: the card may already be engaged.
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_1', 600)->once();
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Failed to persist pending operation cache/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertFalse($result['result']);
    }

    public function testCreateActionRecordsTheRequestedAmountAndLogsWhenTheCreationResponseHasNoPaymentId()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            200,
            '{"operationIds":["op_1"],"execCode":"0000"}',
            null,
            null,
            null
        ));
        $mocks['payment_repository']->shouldReceive('bindPaymentId')->never();
        $mocks['payment_repository']->shouldReceive('bindRequestedAmount')->once()->with('op_1', 1234, 42)->andReturn(true)->globally()->ordered();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;
        $order_action->shouldReceive('createFromOutcome')->once()->with(42, 'op_1', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99'])->globally()->ordered();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/operation op_1 carries no payment id.*cannot be refunded/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * @dataProvider requestedAmountFailureProvider
     */
    public function testCreateActionStillSucceedsWhenRecordingTheRequestedAmountFails(callable $failure)
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            200,
            '{"operationIds":["op_1"],"execCode":"0000"}',
            null,
            null,
            null
        ));
        $failure($mocks['payment_repository']->shouldReceive('bindRequestedAmount')->once()->with('op_1', 1234, 42));
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;
        $order_action->shouldReceive('createFromOutcome')->once()->with(42, 'op_1', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/carries no payment id/'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/requested amount of operation op_1 \(cart 42\) not recorded/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertTrue($result['result']);
    }

    public function requestedAmountFailureProvider()
    {
        return [
            'repository throws' => [function ($expectation) {
                $expectation->andThrow(new \Exception('db down'));
            }],
            'repository returns false' => [function ($expectation) {
                $expectation->andReturn(false);
            }],
        ];
    }

    /**
     * The binding must also happen on the frictionless success path, before the order is created
     * from the synchronous outcome - it is the only moment the payment id is known.
     */
    public function testCreateActionBindsThePaymentIdBeforeCreatingTheOrderOnAFrictionlessSuccess()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            200,
            '{"id":"pay_1","operationIds":["op_1"],"execCode":"0000"}',
            null,
            null,
            null
        ));
        $mocks['payment_repository']->shouldReceive('bindPaymentId')->once()->with('op_1', 'pay_1', 42, 1234)->andReturn(true)->globally()->ordered();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;
        $order_action->shouldReceive('createFromOutcome')->once()->with(42, 'op_1', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99'])->globally()->ordered();
        $mocks['logger']->shouldReceive('error')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams());

        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    /**
     * Fix 6 (PRE-3626 review): UnifiedTokenCache::set() can't propagate a write failure through
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
     * The cart was edited in another tab during the 3DS challenge: the operation captured the
     * amount requested at its creation, which the live cart total no longer matches.
     */
    public function testCreateActionReconcilesAPendingOperationThatPaidTheAmountRequestedAtItsCreation()
    {
        [$action, $mocks] = $this->mockActionForReconciliation(1500, 1500);
        $mocks['order_action']->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_pending', '0000', PaymentOutcome::PAID, 1500, null, [])
            ->andReturn([
                'result' => true,
                'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            ]);

        $result = $action->createAction($this->defaultParams());

        $this->assertTrue($result['result']);
        $this->assertSame('https://shop.example/order-confirmation?id_order=99', $result['redirect_url']);
    }

    public function testCreateActionRefusesAPendingOperationThatPaidAnotherAmountThanRequested()
    {
        [$action, $mocks] = $this->mockActionForReconciliation(1500, 1234);
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::on(function ($message) {
            return false !== strpos($message, 'manual review')
                && false !== strpos($message, 'op_pending')
                && false !== strpos($message, 'cart 42')
                && false !== strpos($message, '1500')
                && false !== strpos($message, '1234');
        }));
        $mocks['cookies']->shouldReceive('setPaymentErrorsCookie')->once();

        $result = $action->createAction($this->defaultParams());

        $this->assertFalse($result['result']);
        $this->assertSame('index.php?controller=order&step=3&has_error=1&modulename=payplug', $result['redirect_url']);
    }

    /**
     * Fix 2 (PRE-3626 review): when reconciling an already in-flight pending operation fails
     * (e.g. it genuinely expired/vanished server-side), createAction() must not hard-fail the
     * whole checkout over it - it falls through and creates a fresh payment instead.
     */
    public function testCreateActionCreatesNewPaymentWhenReconciliationGetOperationThrows()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_stale', 'op_stale', 'op_123');
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
        $action = (new \ReflectionClass(UnifiedPaymentAction::class))->newInstanceWithoutConstructor();

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
        $plugin->shouldReceive('getTools')->andReturnSelf();
        $plugin->shouldReceive('tool')->with('getToken', false)->andReturn(self::STATIC_TOKEN);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.unified.payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        // createAction()'s own pre-existing order_exists check (no order yet). The reconciliation
        // path's own "does the order already exist" check now lives behind the mocked order
        // creator below, so it no longer needs a second getIdByCartId()/get()/isCreated() wiring.
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);
        $amount_helper->shouldReceive('convertAmount')->with(12.34, false, 'USD')->andReturn(1234);

        $factory->shouldReceive('createLogger')->andReturn(\Mockery::mock('Logger'));
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $payment_repository = \Mockery::mock('PaymentRepository');
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('getRequestedAmountByOperationId')->with('op_pending')->andReturn(null);

        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_pending');
        $token_cache->shouldReceive('get')->with('uhf_pending_alias:op_pending')->andReturn('{"alias_id":"alias_new_123","brand":"visa"}');
        $token_cache->shouldReceive('delete')->with('uhf_pending_operation:42')->once();
        $token_cache->shouldReceive('delete')->with('uhf_challenge_html:42')->once();
        $token_cache->shouldReceive('delete')->with('uhf_pending_alias:op_pending')->once();

        $payment_service->shouldReceive('getOperation')->with('op_pending')->once()->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => 1234, 'orderId' => '42']),
        ]);
        $payment_service->shouldReceive('createPayment')->never();

        $lock->shouldReceive('acquire')->with('uhf_cart:42', 60)->andReturn(true);
        $lock->shouldReceive('release')->with('uhf_cart:42')->once();

        // UnifiedOrderAction now has its own dedicated test coverage (UnifiedOrderActionTest) -
        // this suite only needs to verify UnifiedPaymentAction reaches it with the right arguments and
        // relays whatever it returns, not re-exercise its internal order-creation/reconciliation
        // logic (order adapter, validator, context link, ...).
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->with(
                42,
                'op_pending',
                '0000',
                PaymentOutcome::PAID,
                1234,
                ['alias_id' => 'alias_new_123', 'brand' => 'visa'],
                ['last4' => null, 'exp_month' => null, 'exp_year' => null]
            )
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
            'static_token' => self::STATIC_TOKEN,
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
        $action = (new \ReflectionClass(UnifiedPaymentAction::class))->newInstanceWithoutConstructor();

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
        $plugin->shouldReceive('getTools')->andReturnSelf();
        $plugin->shouldReceive('tool')->with('getToken', false)->andReturn(self::STATIC_TOKEN);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.unified.payment_service_factory')->andReturn($factory);

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

        // PRE-3557 rule: a payment still pending never creates an order.
        $action->orderAction = \Mockery::mock('UnifiedOrderAction');
        $action->orderAction->shouldReceive('createFromOutcome')->never();

        $action->dependencies = $dependencies;

        $result = $action->createAction([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
            'static_token' => self::STATIC_TOKEN,
        ]);

        $this->assertSame([
            'result' => true,
            'redirect_url' => 'https://shop.example/module/payplug/unified?action=challenge&token=existing-token-abc',
            'return_url' => 'https://shop.example/module/payplug/unified?action=challenge&token=existing-token-abc',
        ], $result);
    }

    public function testCreateActionReturnsErrorWhenNeitherAliasIdNorHfTokenIsGiven()
    {
        $action = (new \ReflectionClass(UnifiedPaymentAction::class))->newInstanceWithoutConstructor();
        $action->dependencies = \Mockery::mock('Dependencies');

        $this->assertSame([
            'result' => false,
            'message' => 'Missing hfToken/selectedBrand or id_payplug_alias',
        ], $action->createAction(['id_cart' => 42]));
    }

    public function testCreateActionPaysWithAUsableAliasAndCachesNoPendingAliasOnChallenge()
    {
        [$action, $mocks] = $this->mockActionForAliasPayment();

        $mocks['alias_repository']->shouldReceive('findUsable')
            ->once()
            ->with(1, 7, 'usd', 'ident_usd')
            ->andReturn(['id_payplug_unified_alias' => '1', 'alias_id' => 'alias_abc']);
        $mocks['payment_service']->shouldReceive('createPayment')
            ->once()
            ->withArgs(function ($dto) {
                return $dto instanceof PaymentDto
                    && 'alias_abc' === $dto->aliasId
                    && 'ONE_CLICK' === $dto->recurringMode
                    && null === $dto->paymentMethod
                    && '42' === $dto->common->orderId
                    && 1234 === $dto->common->amount;
            })
            ->andReturn(new PaymentOutput(201, '{"id":"op_123","execCode":"0001"}', 'https://acs.example/challenge', null, 'alias_abc'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null, 'op_123');
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with($this->tokenCartKeyMatcher(), '42', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with('uhf_cart_token:42', \Mockery::type('string'), 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with(\Mockery::pattern('/^uhf_pending_alias:/'), \Mockery::any(), \Mockery::any())->never();

        $result = $action->createAction($this->aliasParams());

        $this->assertTrue($result['result']);
        $this->assertSame('https://acs.example/challenge', $result['redirect_url']);
    }

    public function testCreateActionNeverTreatsTheEchoedAliasIdAsANewAliasOnADirectResult()
    {
        [$action, $mocks] = $this->mockActionForAliasPayment();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $mocks['alias_repository']->shouldReceive('findUsable')->once()->andReturn(['id_payplug_unified_alias' => '1', 'alias_id' => 'alias_abc']);
        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(
            new PaymentOutput(201, '{"id":"op_123","execCode":"0000"}', null, null, 'alias_abc')
        );
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_123', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99']);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->aliasParams());

        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testCreateActionRejectsAnAliasNotUsableByTheCustomerBeforeAnyApiCall()
    {
        [$action, $mocks] = $this->mockActionForAliasPayment();

        // findUsable() scopes on customer + lowercase currency + UHF identifier; null covers
        // unknown / other customer / other currency / other identifier alike.
        $mocks['alias_repository']->shouldReceive('findUsable')
            ->once()
            ->with(1, 7, 'usd', 'ident_usd')
            ->andReturn(null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Unknown or unusable alias/'));
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->never();
        $mocks['payment_service']->shouldReceive('getOperation')->never();
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->never();

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($this->aliasParams()));
    }

    /**
     * @dataProvider malformedAliasIdProvider
     *
     * @param string $id_payplug_alias
     */
    public function testCreateActionRejectsAMalformedAliasIdLikeAnUnknownAlias($id_payplug_alias)
    {
        [$action, $mocks] = $this->mockActionForAliasPayment();

        // Strict digits: never read '1abc' as alias 1; findUsable() refuses 0 without querying.
        $mocks['alias_repository']->shouldReceive('findUsable')
            ->once()
            ->with(0, 7, 'usd', 'ident_usd')
            ->andReturn(null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Unknown or unusable alias/'));
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->never();

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($this->aliasParams(['id_payplug_alias' => $id_payplug_alias])));
    }

    public function malformedAliasIdProvider()
    {
        return [
            'digits then letters' => ['1abc'],
            'negative' => ['-1'],
            'decimal' => ['1.5'],
            'unified api alias id' => ['alias_abc'],
        ];
    }

    public function testCreateActionRejectsAnExpiredAliasBeforeAnyApiCall()
    {
        [$action, $mocks] = $this->mockActionForAliasPayment();

        $expired_alias = [
            'id_payplug_unified_alias' => '1',
            'alias_id' => 'alias_abc',
            'exp_month' => '01',
            'exp_year' => '2020',
        ];
        $mocks['alias_repository']->shouldReceive('findUsable')->once()->andReturn($expired_alias);
        $mocks['alias_action']->shouldReceive('isExpired')->once()->with($expired_alias)->andReturn(true);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/expired card/'));
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->never();
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->never();

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($this->aliasParams()));
    }

    public function testCreateActionPaysWithAnUnexpiredAlias()
    {
        [$action, $mocks] = $this->mockActionForAliasPayment();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $unexpired_alias = [
            'id_payplug_unified_alias' => '1',
            'alias_id' => 'alias_abc',
            'exp_month' => '12',
            'exp_year' => '2029',
        ];
        $mocks['alias_repository']->shouldReceive('findUsable')->once()->andReturn($unexpired_alias);
        $mocks['alias_action']->shouldReceive('isExpired')->once()->with($unexpired_alias)->andReturn(false);
        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(
            new PaymentOutput(201, '{"id":"op_123","execCode":"0000"}', null, null, 'alias_abc')
        );
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99']);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $this->assertTrue($action->createAction($this->aliasParams())['result']);
    }

    public function testCreateActionRejectsAnAliasPaymentFromAGuestContextCustomer()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['context']->customer->is_guest = 1;
        $mocks['module']->shouldReceive('getService')->with('payplug.models.repositories.unified_alias')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/guest cart/'));
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->never();

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($this->aliasParams()));
    }

    /**
     * @dataProvider missingContextCustomerProvider
     *
     * @param mixed $customer
     */
    public function testCreateActionRejectsAnAliasPaymentWhenTheContextHasNoCustomer($customer)
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['context']->customer = $customer;
        $mocks['module']->shouldReceive('getService')->with('payplug.models.repositories.unified_alias')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/guest cart/'));
        $mocks['payment_service']->shouldReceive('createPayment')->never();

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($this->aliasParams()));
    }

    public function missingContextCustomerProvider()
    {
        return [
            'no customer' => [null],
            'customer without id' => [(object) ['id' => 0, 'is_guest' => 0]],
        ];
    }

    public function testCreateActionRejectsAnAliasPaymentWhenOneClickIsDisabled()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $this->disableOneClick($mocks);
        $mocks['module']->shouldReceive('getService')->with('payplug.models.repositories.unified_alias')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/one-click disabled/'));
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->never();
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['lock']->shouldReceive('release')->never();

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $action->createAction($this->aliasParams()));
    }

    public function testCreateActionIgnoresSaveCardWhenOneClickIsDisabled()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $this->disableOneClick($mocks);
        $this->expectPaymentWithoutSavedCard($mocks);

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertTrue($result['result']);
    }

    public function testCreateActionIgnoresSaveCardForAGuestContextCustomer()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['context']->customer->is_guest = 1;
        $this->expectPaymentWithoutSavedCard($mocks);

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertTrue($result['result']);
    }

    /**
     * @dataProvider directPendingAliasIdProvider
     *
     * @param string|null $output_alias_id
     * @param string $expected_cached_alias_id
     */
    public function testCreateActionCachesThePendingAliasButCreatesNoOrderOnADirectThreeDsPendingResult($output_alias_id, $expected_cached_alias_id)
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(
            new PaymentOutput(201, '{"id":"op_123","execCode":"0001"}', null, null, $output_alias_id)
        );
        $mocks['token_cache']->shouldReceive('set')
            ->with('uhf_pending_alias:op_123', json_encode(['alias_id' => $expected_cached_alias_id, 'brand' => 'visa']), 86400)
            ->once();
        // Never saved at creation, and no order for a payment still pending (PRE-3557 rule): the
        // later PAID notification creates the order and saves the card.
        $order_action->shouldReceive('createFromOutcome')->never();
        $mocks['logger']->shouldReceive('error')->never();
        $mocks['logger']->shouldReceive('info')->once()->with(\Mockery::pattern('/op_123.*still pending 3DS.*cart 42/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertSame([
            'result' => false,
            'message' => 'The transaction was not completed and your card was not charged.',
        ], $result);
    }

    public function directPendingAliasIdProvider()
    {
        return [
            'alias id returned' => ['alias_new_123', 'alias_new_123'],
            'no alias id yet' => [null, ''],
        ];
    }

    /**
     * @dataProvider fetchedOperationAliasProvider
     *
     * @param array<string, mixed> $alias_fields
     * @param string $expected_alias_id
     */
    public function testCreateActionFallsBackToTheFetchedOperationAliasIdOnADirectPaidResult(array $alias_fields, $expected_alias_id)
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(
            new PaymentOutput(201, '{"id":"op_123","execCode":"0000"}', null, null, null)
        );
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->once()->andReturn([
            'body' => json_encode([
                'execCode' => '0000',
                'paymentMethod' => $alias_fields + [
                    'card' => ['code6x4' => '402205XXXXXX0001'],
                    'details' => ['validityDate' => '2029-12'],
                ],
            ]),
        ]);
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_123', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99']);
        $order_action->shouldReceive('persistAlias')
            ->once()
            ->with(42, ['alias_id' => $expected_alias_id, 'brand' => 'visa'], ['last4' => '0001', 'exp_month' => '12', 'exp_year' => '2029']);
        $mocks['logger']->shouldReceive('error')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertTrue($result['result']);
    }

    public function fetchedOperationAliasProvider()
    {
        return [
            'storedId only' => [['storedId' => 'card_stored'], 'card_stored'],
            'storedId and id' => [['storedId' => 'card_stored', 'id' => 'card_legacy'], 'card_stored'],
            'id only' => [['id' => 'card_legacy'], 'card_legacy'],
        ];
    }

    public function testCreateActionSavesTheDirectPaidAliasFromTheFetchedOperationOnceTheOrderExistsAndTheLockIsReleased()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            json_encode([
                'id' => 'op_123',
                'execCode' => '0000',
                'paymentMethod' => [
                    'card' => ['code6x4' => '402205XXXXXX9999'],
                    'details' => ['validityDate' => '2030-01'],
                ],
            ]),
            null,
            null,
            'alias_new_123'
        ));
        // The order first, then the lock release, and only then the extra API call and the save.
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_123', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->globally()->ordered()
            ->andReturn([
                'result' => true,
                'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
                'persisted' => true,
            ]);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42')->globally()->ordered();
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->once()->globally()->ordered()->andReturn([
            'body' => json_encode([
                'execCode' => '0000',
                'paymentMethod' => [
                    'card' => ['code6x4' => '402205XXXXXX0001'],
                    'details' => ['validityDate' => '2029-12'],
                ],
            ]),
        ]);
        $order_action->shouldReceive('persistAlias')
            ->once()
            ->with(42, ['alias_id' => 'alias_new_123', 'brand' => 'visa'], ['last4' => '0001', 'exp_month' => '12', 'exp_year' => '2029'])
            ->globally()->ordered();
        $mocks['token_cache']->shouldReceive('set')->with(\Mockery::pattern('/^uhf_pending_alias:/'), \Mockery::any(), \Mockery::any())->never();
        $mocks['logger']->shouldReceive('error')->never();

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
        // Internal keys never reach the browser.
        $this->assertArrayNotHasKey('persisted', $result);
    }

    public function testCreateActionSavesNoAliasOnADirectPaidResultWhoseOrderCreationFailed()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(
            new PaymentOutput(201, '{"id":"op_123","execCode":"0000"}', null, null, 'alias_new_123')
        );
        $mocks['payment_service']->shouldReceive('getOperation')->never();
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->andReturn(['result' => false, 'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug']);
        $order_action->shouldReceive('persistAlias')->never();
        $mocks['cookies']->shouldReceive('setPaymentErrorsCookie')->once();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertFalse($result['result']);
    }

    public function testCreateActionCachesThePendingAliasByOperationIdWhenSavingACardHitsAChallenge()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(new PaymentOutput(
            201,
            '{"id":"op_123","execCode":"0001"}',
            'https://acs.example/challenge',
            null,
            'alias_new_123'
        ));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null, 'op_123');
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with($this->tokenCartKeyMatcher(), '42', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with('uhf_cart_token:42', \Mockery::type('string'), 600)->once();
        $mocks['token_cache']->shouldReceive('set')
            ->with('uhf_pending_alias:op_123', '{"alias_id":"alias_new_123","brand":"visa"}', 86400)
            ->once();

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertTrue($result['result']);
    }

    public function testCreateActionContinuesWithoutAliasWhenTheFetchedOperationCarriesNoAliasId()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(
            new PaymentOutput(201, '{"id":"op_123","execCode":"0000"}', null, null, null)
        );
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->once()->andReturn([
            'body' => '{"execCode":"0000"}',
        ]);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/no alias id returned/'));
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_123', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99']);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertTrue($result['result']);
    }

    public function testCreateActionStillCreatesTheOrderWithoutAliasWhenFetchingThePaidOperationFails()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order_action = \Mockery::mock('OrderAction');
        $action->orderAction = $order_action;

        $mocks['payment_service']->shouldReceive('createPayment')->once()->andReturn(
            new PaymentOutput(201, '{"id":"op_123","execCode":"0000"}', null, null, 'alias_new_123')
        );
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_123')->once()->andThrow(new \Exception('unified API down'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/getOperation failed, alias not saved/'));
        $order_action->shouldReceive('createFromOutcome')
            ->once()
            ->with(42, 'op_123', '0000', PaymentOutcome::PAID, 1234, null, [])
            ->andReturn(['result' => true, 'redirect_url' => 'https://shop.example/order-confirmation?id_order=99']);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');

        $result = $action->createAction($this->defaultParams(['save_card' => 1, 'cardholder' => 'Jane Doe']));

        $this->assertTrue($result['result']);
    }

    /**
     * Common setup for the tests above: reaches UnifiedPaymentAction::createAction()'s try block
     * (lock acquired, currency/cart/order validated), letting each test configure only what
     * happens with the payment-creation call itself.
     *
     * @return array{0: UnifiedPaymentAction, 1: array<string, object>}
     */
    private function mockActionForPaymentCreation()
    {
        $action = (new \ReflectionClass(UnifiedPaymentAction::class))->newInstanceWithoutConstructor();

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

        $configuration_class = \Mockery::mock('ConfigurationClass');

        $context = (object) [
            'cart' => (object) ['id' => 42],
            'link' => $link,
            'customer' => (object) ['id' => 7, 'is_guest' => 0],
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
        $cookies = \Mockery::mock('CookiesHelper');
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $dependencies->shouldReceive('getHelpers')->andReturn([
            'amount' => $amount_helper,
            'cookies' => $cookies,
        ]);

        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCustomer')->andReturn($customer_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getTools')->andReturnSelf();
        $plugin->shouldReceive('tool')->with('getToken', false)->andReturn(self::STATIC_TOKEN);
        $plugin->shouldReceive('tool')->with('getRemoteAddr')->andReturn('127.0.0.1');
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class);
        // One-click enabled by default; see disableOneClick().
        $configuration_class->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn(json_encode(['one_click' => true]))
            ->byDefault();

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.unified.payment_service_factory')->andReturn($factory);

        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')->with('USD')->andReturn(true);
        $prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('USD')->andReturn('ident_usd');

        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart)->byDefault();
        $currency_adapter->shouldReceive('getCurrency')->with(2)->andReturn($currency);
        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0)->byDefault();
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null)->byDefault();
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false])->byDefault();
        $customer_adapter->shouldReceive('get')->with(7)->andReturn($customer)->byDefault();
        $amount_helper->shouldReceive('convertAmount')->with(12.34, false, 'USD')->andReturn(1234);

        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('create')->andReturn($payment_service);
        $payment_repository = \Mockery::mock('PaymentRepository');
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $payment_repository->shouldReceive('bindPaymentId')->andReturn(true)->byDefault();
        $payment_repository->shouldReceive('bindRequestedAmount')->andReturn(true)->byDefault();
        $payment_repository->shouldReceive('getRequestedAmountByOperationId')->andReturn(null)->byDefault();
        $lock->shouldReceive('acquire')->with('uhf_cart:42', 60)->andReturn(true);
        // Fix 2's pending-operation reconciliation check: no pending operation cached by default.
        // Tests that go on to cache one (via a redirectHtml/redirectUrl response) override this
        // with a second return value for Fix 6's read-after-write check.
        $token_cache->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null)->byDefault();

        $link->shouldReceive('getModuleLink')->with('payplug', 'unified', $this->tokenActionParams('return'), true)
            ->andReturn('https://shop.example/module/payplug/unified?action=return&id_cart=42');
        $link->shouldReceive('getModuleLink')->with('payplug', 'notify', [], true)
            ->andReturn('https://shop.example/module/payplug/notify')->byDefault();

        $action->dependencies = $dependencies;

        return [$action, [
            'logger' => $logger,
            'lock' => $lock,
            'token_cache' => $token_cache,
            'payment_service' => $payment_service,
            'module' => $module,
            'cart_adapter' => $cart_adapter,
            'customer_adapter' => $customer_adapter,
            'context' => $context,
            'configuration_class' => $configuration_class,
            'cookies' => $cookies,
            'payment_repository' => $payment_repository,
            'link' => $link,
            'order_adapter' => $order_adapter,
            'validator' => $validator,
            'plugin' => $plugin,
        ]];
    }

    /**
     * A PAID operation already in flight for the cart, the cart total converting to 1234.
     *
     * @param int $paid_amount amount of the operation fetched back
     * @param int|null $requested_amount amount recorded when the operation was created
     *
     * @return array{0: UnifiedPaymentAction, 1: array<string, object>}
     */
    private function mockActionForReconciliation($paid_amount, $requested_amount)
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();
        $order_action = \Mockery::mock('OrderAction');
        $order_action->shouldReceive('errorUrl')->andReturn('index.php?controller=order&step=3&has_error=1&modulename=payplug');
        $action->orderAction = $order_action;

        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn('op_pending');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_alias:op_pending')->andReturn(null);
        $mocks['token_cache']->shouldReceive('delete');
        $mocks['payment_service']->shouldReceive('getOperation')->with('op_pending')->once()->andReturn([
            'body' => json_encode(['execCode' => '0000', 'amount' => $paid_amount, 'orderId' => '42']),
        ]);
        $mocks['payment_service']->shouldReceive('createPayment')->never();
        $mocks['payment_repository']->shouldReceive('getRequestedAmountByOperationId')
            ->with('op_pending')
            ->andReturn($requested_amount);
        $mocks['lock']->shouldReceive('release');
        $mocks['order_action'] = $order_action;

        return [$action, $mocks];
    }

    /**
     * @param array<string, object> $mocks
     */
    private function disableOneClick(array $mocks)
    {
        $mocks['configuration_class']->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn(json_encode(['one_click' => false]));
    }

    /**
     * Expects a plain hfToken payment hitting a challenge: no saveFutureUsage, no pending alias.
     *
     * @param array<string, object> $mocks
     */
    private function expectPaymentWithoutSavedCard(array $mocks)
    {
        $mocks['payment_service']->shouldReceive('createPayment')
            ->once()
            ->withArgs(function ($hosted_field_dto) {
                return !isset($hosted_field_dto->paymentMethod['saveFutureUsage'])
                    && null === $hosted_field_dto->recurringMode
                    && !isset($hosted_field_dto->paymentMethod['details']['fullName']);
            })
            ->andReturn(new PaymentOutput(201, '{"id":"op_123","execCode":"0001"}', 'https://acs.example/challenge', null, null));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:42');
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_operation:42')->andReturn(null, null, 'op_123');
        $mocks['token_cache']->shouldReceive('set')->with('uhf_pending_operation:42', 'op_123', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with($this->tokenCartKeyMatcher(), '42', 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with('uhf_cart_token:42', \Mockery::type('string'), 600)->once();
        $mocks['token_cache']->shouldReceive('set')->with(\Mockery::pattern('/^uhf_pending_alias:/'), \Mockery::any(), \Mockery::any())->never();
        $mocks['logger']->shouldReceive('error')->never();
    }

    private function defaultParams(array $overrides = [])
    {
        return array_merge([
            'hfToken' => 'hf_tok_123',
            'selectedBrand' => 'visa',
            'id_cart' => 42,
            'save_card' => 0,
            'static_token' => self::STATIC_TOKEN,
        ], $overrides);
    }

    /**
     * @return array{0: UnifiedPaymentAction, 1: array<string, object>}
     */
    private function mockActionForAliasPayment()
    {
        [$action, $mocks] = $this->mockActionForPaymentCreation();

        $alias_repository = \Mockery::mock('UnifiedAliasRepository');
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.models.repositories.unified_alias')
            ->andReturn($alias_repository);
        $alias_action = \Mockery::mock('UnifiedAliasAction');
        $alias_action->shouldReceive('isExpired')->andReturn(false)->byDefault();
        $mocks['module']->shouldReceive('getService')
            ->with('payplug.action.unified_alias')
            ->andReturn($alias_action);

        return [$action, $mocks + ['alias_repository' => $alias_repository, 'alias_action' => $alias_action]];
    }

    private function aliasParams(array $overrides = [])
    {
        return array_merge([
            'id_payplug_alias' => '1',
            'id_cart' => 42,
            'static_token' => self::STATIC_TOKEN,
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
