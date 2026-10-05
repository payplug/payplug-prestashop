<?php

// tests/actions/OperationAction/notifyActionTest.php

namespace PayPlug\tests\actions\OperationAction;

use PayPlug\src\actions\OperationAction;
use PayplugUnifiedCore\DataValues\OperationData;
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
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
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
        $this->mockGetOperationResponse($mocks, 'op_failed', '9999', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, 'op_failed', '9999', PaymentOutcome::FAILED, 2900, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
            'persisted' => false,
        ]);
        $mocks['payment_repository']->shouldReceive('save')->once()->withArgs(function ($operation_data) {
            return 'op_failed' === $operation_data->operationId && PaymentOutcome::FAILED === $operation_data->outcome;
        });
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('op_failed');

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testDoesNotOverwriteAnAlreadyPersistedRowWhenOutcomeIsFailedAndOrderAlreadyExisted()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $failed_body = '{"id":"op_failed","execCode":"9999","orderId":"6","amount":2900}';
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn($failed_body);
        $this->mockGetOperationResponse($mocks, 'op_failed', '9999', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, 'op_failed', '9999', PaymentOutcome::FAILED, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ]);
        $mocks['payment_repository']->shouldReceive('save')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('op_failed');

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testReturnsServerErrorAndDoesNotMarkTreatedWhenOrderCreationFailsForNonFailedOutcome()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);

        // UnifiedOrderAction's own validateOrder()-throws handling has its own dedicated
        // coverage in UnifiedOrderActionTest - this suite only needs the mocked order creator to
        // report failure the same way createFromOutcome() would for a PAID outcome.
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
            'persisted' => false,
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
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', 'not-a-number', 2900);
        $mocks['lock']->shouldReceive('acquire')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Invalid orderId in getOperation response/'));

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

    /**
     * Observed on staging (2026-09-29): the payment notification lands while returnAction() is
     * still creating the order under the cart lock, and the Receiver never retries a 409 - so the
     * webhook must wait for that lock rather than give up after returnAction()'s short budget.
     */
    public function testWaitsForTheBrowserReturnToReleaseTheCartLockInsteadOfReturningConflict()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $action->lockRetryDelayUsec = 0;
        $mocks['lock']->shouldReceive('acquire')->times(6)->with('uhf_cart:6', 60)
            ->andReturn(false, false, false, false, false, true);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_cart:6');
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('359fe258-8264-4a90-9a40-d16e1736058d');

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testReturnsConflictWhenLockCannotBeAcquired()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        // 1 initial attempt + OperationAction::NOTIFY_LOCK_RETRY_ATTEMPTS (40) retries = 41 total:
        // the webhook waits longer than returnAction() - the Receiver doesn't retry a 409.
        $action->lockRetryDelayUsec = 0;
        $mocks['lock']->shouldReceive('acquire')->times(41)->with('uhf_cart:6', 60)->andReturn(false);
        $mocks['lock']->shouldReceive('release')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Could not acquire lock/'));

        $result = $action->notifyAction();

        $this->assertSame(409, $result['http_status']);
    }

    public function testDoesNotPersistOrMarkTreatedWhenAmountDoesNotMatchTheCartTotal()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        // The server's own getOperation() response claims amount 2900; the cart's own actual
        // total converts to something else.
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 1500);
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['payment_repository']->shouldReceive('save')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/amount mismatch/'));

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testAcceptsMatchingAuthorizationHeaderWhenSecretIsConfigured()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer expected-secret';

        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $mocks['configuration_repository']->shouldReceive('get')->with('webhook_authorization_header')->andReturn('Bearer expected-secret');
        $mocks['logger']->shouldReceive('error');
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
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
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);

        unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    public function testReadsCardDetailsFromTheGetOperationResponseNeverFromTheWebhookBody()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $forged_body = json_encode([
            'id' => '359fe258-8264-4a90-9a40-d16e1736058d',
            'execCode' => '0000',
            'orderId' => '6',
            'amount' => 2900,
            'paymentMethod' => [
                'card' => ['code6x4' => '411111XXXXXX9999'],
                'details' => ['validityDate' => '2031-01'],
            ],
        ]);
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn($forged_body);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900, [
            'paymentMethod' => [
                'card' => ['code6x4' => '402205XXXXXX0001'],
                'details' => ['validityDate' => '2029-12'],
            ],
        ]);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['token_cache']->shouldReceive('get')
            ->with('uhf_pending_alias:359fe258-8264-4a90-9a40-d16e1736058d')
            ->andReturn('{"alias_id":"alias_new_123","brand":"visa"}');
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ], ['alias_id' => 'alias_new_123', 'brand' => 'visa'], ['last4' => '0001', 'exp_month' => '12', 'exp_year' => '2029']);
        $mocks['token_cache']->shouldReceive('delete')->once()->with('uhf_pending_alias:359fe258-8264-4a90-9a40-d16e1736058d');
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('359fe258-8264-4a90-9a40-d16e1736058d');

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testResolvesTheAliasIdFromTheGetOperationResponseWhenTheMarkerHasNone()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900, [
            'paymentMethod' => [
                'id' => 'card_alias',
                'card' => ['code6x4' => '402205XXXXXX0001', 'type' => 'VISA', 'network' => 'VISA'],
                'details' => ['validityDate' => '2029-12', 'selectedBrand' => 'VISA'],
            ],
        ]);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['token_cache']->shouldReceive('get')
            ->with('uhf_pending_alias:359fe258-8264-4a90-9a40-d16e1736058d')
            ->andReturn('{"alias_id":"","brand":"visa"}');
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ], ['alias_id' => 'card_alias', 'brand' => 'visa'], ['last4' => '0001', 'exp_month' => '12', 'exp_year' => '2029']);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testNeverReadsTheAliasIdFromTheWebhookBody()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $forged_body = json_encode([
            'id' => '359fe258-8264-4a90-9a40-d16e1736058d',
            'execCode' => '0000',
            'orderId' => '6',
            'amount' => 2900,
            'paymentMethod' => ['id' => 'forged_alias'],
        ]);
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn($forged_body);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['token_cache']->shouldReceive('get')
            ->with('uhf_pending_alias:359fe258-8264-4a90-9a40-d16e1736058d')
            ->andReturn('{"alias_id":"","brand":"visa"}');
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ], null, []);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testSavesNoAliasWithoutAPendingMarkerEvenIfTheResponseCarriesAnAliasId()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900, [
            'paymentMethod' => ['id' => 'card_alias', 'card' => ['code6x4' => '402205XXXXXX0001']],
        ]);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ], null, []);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testClearsThePendingAliasKeyOnFailedOutcome()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $failed_body = '{"id":"op_failed","execCode":"9999","orderId":"6","amount":2900}';
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn($failed_body);
        $this->mockGetOperationResponse($mocks, 'op_failed', '9999', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['token_cache']->shouldReceive('get')->with('uhf_pending_alias:op_failed')->andReturn('{"alias_id":"alias_new_123","brand":"visa"}');
        $this->mockCreateFromOutcome($mocks, 'op_failed', '9999', PaymentOutcome::FAILED, 2900, [
            'result' => false,
            'redirect_url' => 'index.php?controller=order&step=3&has_error=1&modulename=payplug',
            'persisted' => false,
        ], ['alias_id' => 'alias_new_123', 'brand' => 'visa'], ['last4' => null, 'exp_month' => null, 'exp_year' => null]);
        $mocks['token_cache']->shouldReceive('delete')->once()->with('uhf_pending_alias:op_failed');
        $mocks['payment_repository']->shouldReceive('save')->once();
        $mocks['payment_repository']->shouldReceive('markTreated')->once()->with('op_failed');

        $result = $action->notifyAction();

        $this->assertSame(200, $result['http_status']);
    }

    public function testConfirmsARecordedRefundWithoutEverTouchingTheOrder()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $this->mockGetOperationResponse($mocks, 'op_refund_1', '0000', '6', 1000);
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->once()->with('op_refund_1', 'confirmed')->andReturn(true);
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['payment_repository']->shouldReceive('isTreated')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testMarksARecordedRefundFailedOnANonSuccessOutcome()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"4001","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $this->mockGetOperationResponse($mocks, 'op_refund_1', '4001', '6', 1000);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Refund op_refund_1 reported a non-success outcome/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->once()->with('op_refund_1', 'failed')->andReturn(true);
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testIgnoresTheRedeliveryOfAnAlreadySettledRefund()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'confirmed']);
        $mocks['factory']->shouldReceive('create')->never();
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testLeavesARecordedRefundUntouchedWhenTheAmountDiffers()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $this->mockGetOperationResponse($mocks, 'op_refund_1', '0000', '6', 999);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/amount mismatch/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testLeavesARecordedRefundUntouchedAndAsksForARetryWhenTheExecCodeIsMissing()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $mocks['factory']->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')->once()->with('op_refund_1')
            ->andReturn(['body' => '{"orderId":"6","amount":1000}']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/no execCode/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(500, $action->notifyAction()['http_status']);
    }

    public function testReturnsServerErrorWhenARecordedRefundCannotBeFetched()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $mocks['factory']->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')->once()->andThrow(new \Exception('timeout'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/getOperation failed for refund/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(500, $action->notifyAction()['http_status']);
    }

    public function testAsksForARetryWhenAPaidOperationTargetsAnOrderAlreadyPaidByAnotherOperation()
    {
        // e.g. a full refund notification arriving before refundAction() recorded its row
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(6)->andReturn(99);
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')
            ->andReturn(new OperationData('op_original', '0000', PaymentOutcome::PAID, 2900, '99'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/is not the paid operation/'));
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();

        $this->assertSame(409, $action->notifyAction()['http_status']);
    }

    public function testIgnoresANonPaidOperationTargetingAnOrderAlreadyPaidByAnotherOperation()
    {
        // e.g. the late webhook of an earlier failed attempt on a cart that has since been paid
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_failed","execCode":"9999","orderId":"6","amount":2900}');
        $this->mockGetOperationResponse($mocks, 'op_failed', '9999', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(6)->andReturn(99);
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')
            ->andReturn(new OperationData('op_original', '0000', PaymentOutcome::PAID, 2900, '99'));
        $mocks['logger']->shouldReceive('info')->once()->with(\Mockery::pattern('/ignored/'));
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['payment_repository']->shouldReceive('save')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testStillReconcilesAPendingOrderWithItsOwnFinalNotification()
    {
        // The order exists (created pending by returnAction()) but has no PAID operation yet.
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(6)->andReturn(99);
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')->andReturn(null);
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testStillCreatesTheOrderWhenTheNotifiedOperationIsTheOneThatAlreadyPaidIt()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')->andReturn(self::VALID_BODY);
        $this->mockGetOperationResponse($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', '6', 2900);
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(6)->andReturn(99);
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')
            ->andReturn(new OperationData('359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, '99'));
        $this->mockCreateFromOutcome($mocks, '359fe258-8264-4a90-9a40-d16e1736058d', '0000', PaymentOutcome::PAID, 2900, [
            'result' => true,
            'redirect_url' => 'https://shop.example/order-confirmation?id_order=99',
            'persisted' => true,
        ]);
        $mocks['payment_repository']->shouldReceive('markTreated')->once();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testLeavesARecordedRefundUntouchedAndAsksForARetryWhenTheAmountIsMissing()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $mocks['factory']->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')->once()->with('op_refund_1')
            ->andReturn(['body' => '{"execCode":"0000","orderId":"6"}']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/has no amount/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(500, $action->notifyAction()['http_status']);
    }

    public function testLeavesARecordedRefundPendingWhileItsReReadIsStillThreeDsPending()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $this->mockGetOperationResponse($mocks, 'op_refund_1', '0001', '6', 1000);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/Refund op_refund_1 reported an undocumented execCode 0001, left pending/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    /**
     * PRE-3627 review: ExecCodeMapper maps any code it doesn't know to FAILED, which would free
     * an amount that may already have left for a second refund. Only documented failures fail it.
     */
    public function testLeavesARecordedRefundPendingOnAnUndocumentedExecCode()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"9999","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $this->mockGetOperationResponse($mocks, 'op_refund_1', '9999', '6', 1000);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/undocumented execCode 9999, left pending.*manual check needed/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    /**
     * PRE-3627 review (M2): refundAction() holds the refund lock until the refund's row carries
     * its operation id. A notification beating that is matched once the lock is free - even a
     * partial one, which would otherwise be dropped by the cart-amount cross-check.
     */
    public function testMatchesAnEarlyRefundNotificationOnceTheRefundInProgressReleasesItsLock()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $action->lockRetryDelayUsec = 0;
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')->twice()
            ->andReturn(null, ['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $mocks['factory']->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')->twice()->with('op_refund_1')
            ->andReturn(['body' => '{"execCode":"0000","orderId":"6","amount":1000}']);
        // The cart total (2900) differs from this partial refund's amount (1000).
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(6)->andReturn(99);
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')
            ->andReturn(new OperationData('op_original', '0000', PaymentOutcome::PAID, 2900, '99'));
        $mocks['lock']->shouldReceive('acquire')->times(3)->with('uhf_refund:99', 5)->andReturn(false, false, true);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:99');
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->once()->with('op_refund_1', 'confirmed')->andReturn(true);
        $mocks['order_action']->shouldReceive('createFromOutcome')->never();
        $mocks['payment_repository']->shouldReceive('markTreated')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testStillAsksForARetryWhenTheRefundLockIsNeverReleased()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $action->lockRetryDelayUsec = 0;
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $this->mockGetOperationResponse($mocks, 'op_refund_1', '0000', '6', 1000);
        // The cart total (2900) differs from this partial refund's amount (1000).
        $this->mockCartForAmountCrossCheck($action->dependencies, 2900);
        $mocks['order_adapter']->shouldReceive('getIdByCartId')->with(6)->andReturn(99);
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('99')
            ->andReturn(new OperationData('op_original', '0000', PaymentOutcome::PAID, 2900, '99'));
        // 1 initial attempt + OperationAction::NOTIFY_LOCK_RETRY_ATTEMPTS (40) retries.
        $mocks['lock']->shouldReceive('acquire')->times(41)->with('uhf_refund:99', 5)->andReturn(false);
        $mocks['lock']->shouldReceive('release')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/is not the paid operation/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(409, $action->notifyAction()['http_status']);
    }

    public function nonTerminalRefundExecCodeProvider()
    {
        return [
            'waiting provider' => ['0002'],
            'waiting status' => ['0003'],
            'timeout, result notified later' => ['5004'],
        ];
    }

    /**
     * @dataProvider nonTerminalRefundExecCodeProvider
     *
     * @param string $exec_code
     */
    public function testLeavesARecordedRefundPendingWhileItsReReadIsANonTerminalExecCode($exec_code)
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending']);
        $this->mockGetOperationResponse($mocks, 'op_refund_1', $exec_code, '6', 1000);
        $mocks['logger']->shouldReceive('info')->once()->with(\Mockery::pattern('/Refund op_refund_1 still in progress \(execCode ' . $exec_code . '\)/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
    }

    public function testIgnoresTheRedeliveryOfAnAlreadyFailedRefund()
    {
        [$action, $mocks] = $this->mockActionForNotify();
        $mocks['tools_adapter']->shouldReceive('tool')->with('file_get_contents', 'php://input')
            ->andReturn('{"id":"op_refund_1","execCode":"0000","orderId":"6","amount":1000}');
        $mocks['refund_repository']->shouldReceive('getByRefundOperationId')->with('op_refund_1')
            ->andReturn(['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'failed']);
        $mocks['factory']->shouldReceive('create')->never();
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();

        $this->assertSame(200, $action->notifyAction()['http_status']);
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

        $refund_repository = \Mockery::mock('UpcRefundRepository');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $module->shouldReceive('getService')
            ->with('payplug.models.repositories.upc_refund')
            ->andReturn($refund_repository);
        $refund_repository->shouldReceive('getByRefundOperationId')->andReturn(null)->byDefault();
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $order_adapter->shouldReceive('getIdByCartId')->andReturn(0)->byDefault();
        $payment_repository->shouldReceive('getPaidByOrderId')->andReturn(null)->byDefault();

        // Fix 7: cache cleanup after a terminal reconciliation - not asserted by every test, only
        // the ones specifically covering it.
        $token_cache = \Mockery::mock('TokenCache');
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache)->byDefault();
        $token_cache->shouldReceive('delete')->byDefault();
        $token_cache->shouldReceive('get')->with(\Mockery::pattern('/^uhf_pending_alias:/'))->andReturn(null)->byDefault();

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
            'factory' => $factory,
            'refund_repository' => $refund_repository,
            'order_adapter' => $order_adapter,
        ]];
    }

    /**
     * @param array<string, object> $mocks
     * @param string $operation_id
     * @param string $exec_code
     * @param string $order_id
     * @param int $amount
     * @param array<string, mixed> $extra
     */
    private function mockGetOperationResponse(array $mocks, $operation_id, $exec_code, $order_id, $amount, array $extra = [])
    {
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $mocks['factory']->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')
            ->once()
            ->with($operation_id)
            ->andReturn([
                'body' => json_encode(array_merge([
                    'execCode' => $exec_code,
                    'orderId' => $order_id,
                    'amount' => $amount,
                ], $extra)),
            ]);

        return $payment_service;
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
     * @param array{result: bool, redirect_url: string, persisted?: bool} $return
     * @param array{alias_id: string, brand: string}|null $pending_alias
     * @param array<string, string|null> $card_details
     */
    private function mockCreateFromOutcome(array $mocks, $operation_id, $exec_code, $outcome, $amount, array $return, $pending_alias = null, array $card_details = [])
    {
        $mocks['order_action']->shouldReceive('createFromOutcome')
            ->once()
            ->with(6, $operation_id, $exec_code, $outcome, $amount, $pending_alias, $card_details)
            ->andReturn($return);
    }
}
