<?php

namespace PayPlug\tests\actions\UnifiedRefundAction;

use PayPlug\src\actions\UnifiedRefundAction;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Exceptions\ApiException;
use PayplugUnifiedCore\Exceptions\InvalidRefundRequestException;
use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;
use PayplugUnifiedCore\Exceptions\RefundAmountException;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group unified_refund_action
 */
class refundActionTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testRejectsANonPositiveAmountWithoutCallingTheApi()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['factory']->shouldReceive('create')->never();

        $result = $action->refundAction(42, 0);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.format', $result['message']);
    }

    public function testRejectsAnOrderNotCreatedByThisModule()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['order']->module = 'another_module';
        $mocks['factory']->shouldReceive('create')->never();

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    public function testRejectsAnOrderWithoutAPaidUhfOperation()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_repository']->shouldReceive('getPaidByOrderId')->with('42')->andReturn(null);
        $mocks['factory']->shouldReceive('create')->never();

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    public function testRejectsAndLogsWhenNoAccountIdIsConfiguredForTheOrderCurrency()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['prestashop_adapter']->shouldReceive('getHostedFieldsIdentifier')->with('USD')->andReturn('');
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/misconfigured/'));
        $mocks['lock']->shouldReceive('acquire')->never();

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    /**
     * PRE-3627: the refund endpoint is keyed by the payment's own id, not the operation id. An
     * order paid before that id was recorded can't be refunded from here - fail before any lock
     * or API call rather than send the operation id and get a 400 "transaction not found".
     */
    public function testRejectsAndLogsWhenNoPaymentIdIsRecordedForTheOperation()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_repository']->shouldReceive('getPaymentIdByOperationId')->with('op_pay')->andReturn(null);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/no Unified API payment id recorded for operation op_pay/'));
        $mocks['lock']->shouldReceive('acquire')->never();
        $mocks['factory']->shouldReceive('create')->never();

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    public function testRejectsWhenAnotherRefundHoldsTheLock()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['lock']->shouldReceive('acquire')->with('uhf_refund:42', 90)->andReturn(false);
        $mocks['factory']->shouldReceive('create')->never();
        $mocks['lock']->shouldReceive('release')->never();

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    public function testRejectsAnAmountAboveTheRemainingRefundableAmountAndReleasesTheLock()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['refund_repository']->shouldReceive('getRefundedAmount')->with('42')->andReturn(2000);
        $mocks['payment_validator']->shouldReceive('isRefundableAmount')->with(1000, 900)
            ->andReturn(['result' => false, 'code' => 'upper']);
        $mocks['factory']->shouldReceive('create')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.upper', $result['message']);
    }

    public function testRejectsWhenNothingIsLeftToRefund()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['refund_repository']->shouldReceive('getRefundedAmount')->with('42')->andReturn(2900);
        $mocks['payment_validator']->shouldReceive('isRefundableAmount')->never();
        $mocks['factory']->shouldReceive('create')->never();

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.upper', $result['message']);
    }

    public function testPartialRefundCallsUpcRecordsAPendingRowAndMovesTheOrderToPartialRefund()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')
            ->once()
            ->with('pay_1', 'acc_usd', '6', 'Refund for order XKBKNABJK', null, 1000, 'USD')
            ->andReturn(['status' => 201, 'body' => '{"operationIds":["op_refund_1"]}']);
        $mocks['refund_repository']->shouldReceive('addRefund')->once()->with(\Mockery::pattern('/^intent:/'), 'op_pay', '42', 1000, 'USD')->andReturn(true);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->once()->with(\Mockery::pattern('/^intent:/'), 'op_refund_1')->andReturn(true);
        $mocks['order_class']->shouldReceive('updateOrderState')->once()->with($mocks['order'], 15);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $result = $action->refundAction(42, 1000);

        $this->assertTrue($result['result']);
        $this->assertSame('refund.success', $result['message']);
        $this->assertSame('<div>panel</div>', $result['template']);
        $this->assertTrue($result['reload']);
    }

    public function testRefundingTheExactRemainingAmountMovesTheOrderToRefund()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['refund_repository']->shouldReceive('getRefundedAmount')->with('42')->andReturn(900);
        $mocks['payment_validator']->shouldReceive('isRefundableAmount')->with(2000, 2000)->andReturn(['result' => true]);
        $mocks['payment_service']->shouldReceive('createRefund')
            ->once()
            ->with('pay_1', 'acc_usd', '6', 'Refund for order XKBKNABJK', null, 2000, 'USD')
            ->andReturn(['status' => 201, 'body' => '{"operationIds":["op_refund_2"]}']);
        $mocks['refund_repository']->shouldReceive('addRefund')->once()->with(\Mockery::pattern('/^intent:/'), 'op_pay', '42', 2000, 'USD')->andReturn(true);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->once()->with(\Mockery::pattern('/^intent:/'), 'op_refund_2')->andReturn(true);
        $mocks['order_class']->shouldReceive('updateOrderState')->once()->with($mocks['order'], 7);

        $result = $action->refundAction(42, 2000);

        $this->assertTrue($result['result']);
    }

    public function testUsesTheTestOrderStatesInSandboxMode()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['configuration_class']->shouldReceive('getValue')->with('sandbox_mode')->andReturn('1');
        $mocks['configuration_class']->shouldReceive('getValue')->with('order_state_partial_refund_test')->andReturn('25');
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 201, 'body' => '{"operationIds":["op_refund_1"]}']);
        $mocks['refund_repository']->shouldReceive('addRefund')->once()->andReturn(true);
        $mocks['order_class']->shouldReceive('updateOrderState')->once()->with($mocks['order'], 25);

        $result = $action->refundAction(42, 1000);

        $this->assertTrue($result['result']);
    }

    /**
     * The top-level "id" of a createRefund() response is the PAYMENT id (confirmed against the
     * staging API, 2026-09-29), shared by every refund of that payment - using it as the refund id
     * would collide on the UNIQUE key from the second refund on.
     */
    public function testNeverUsesTheTopLevelIdWhichIsThePaymentId()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 200, 'body' => '{"id":"pay_1","execCode":"0000"}']);
        $mocks['refund_repository']->shouldReceive('addRefund')->once()
            ->with(\Mockery::pattern('/^intent:/'), 'op_pay', '42', 1000, 'USD')
            ->andReturn(true);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->never();
        $mocks['order_class']->shouldReceive('updateOrderState')->once();

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    public function testKeepsTheIntentIdAndLogsOnlyTheResponseKeysWhenTheResponseCarriesNoRefundId()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 200, 'body' => '{"execCode":"0000","descriptor":"SECRET SHOP DESCRIPTOR"}']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::on(function ($message) {
            return false !== strpos($message, 'no refund operation id')
                && false !== strpos($message, 'execCode, descriptor')
                && false === strpos($message, 'SECRET SHOP DESCRIPTOR');
        }));
        $mocks['refund_repository']->shouldReceive('addRefund')->once()
            ->with(\Mockery::pattern('/^intent:/'), 'op_pay', '42', 1000, 'USD')
            ->andReturn(true);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->never();
        $mocks['order_class']->shouldReceive('updateOrderState')->once();

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    /**
     * A 2xx createRefund() response still carries the refund's own execCode ("0000" on success,
     * confirmed against the staging API): a synchronous decline must not be recorded as a refund
     * nor move the order to a refund state - no money moved.
     */

    /**
     * A 2xx response without execCode is still treated as accepted (refusing it would invite a
     * second real refund), but it is logged: it would mean the API contract changed.
     */
    public function testAcceptsButLogsA2xxRefundResponseWithoutExecCode()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 200, 'body' => '{"operationIds":["op_refund_1"],"descriptor":"SECRET SHOP"}']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::on(function ($message) {
            return false !== strpos($message, 'no execCode, assumed accepted')
                && false !== strpos($message, 'operationIds, descriptor')
                && false === strpos($message, 'SECRET SHOP');
        }));
        $mocks['refund_repository']->shouldReceive('addRefund')->once()->with(\Mockery::pattern('/^intent:/'), 'op_pay', '42', 1000, 'USD')->andReturn(true);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->once()->with(\Mockery::pattern('/^intent:/'), 'op_refund_1')->andReturn(true);
        $mocks['order_class']->shouldReceive('updateOrderState')->once();

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    public function testMarksARefundDeclinedSynchronouslyFailedWithoutChangingTheOrderState()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 200, 'body' => '{"id":"pay_1","execCode":"4001","message":"Refused","operationIds":["op_refund_x"]}']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/declined.*order 42.*execCode 4001/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->once()->with(\Mockery::pattern('/^intent:/'), 'failed')->andReturn(true);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->never();
        $mocks['order_class']->shouldReceive('updateOrderState')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    /**
     * @dataProvider nonTerminalExecCodeProvider
     *
     * @param string $exec_code
     */
    public function testRecordsARefundStillInProgressAsPending($exec_code)
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 200, 'body' => '{"execCode":"' . $exec_code . '","operationIds":["op_refund_1"]}']);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->once()->with(\Mockery::pattern('/^intent:/'), 'op_refund_1')->andReturn(true);
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();
        $mocks['order_class']->shouldReceive('updateOrderState')->once();

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    public function nonTerminalExecCodeProvider()
    {
        // 5004: "Timeout. The result will be sent to the notification URL."
        return [['0002'], ['0003'], ['5004']];
    }

    public function testKeepsARefundWithAnUndocumentedExecCodePendingAndAsksToCheckThePortal()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 200, 'body' => '{"execCode":"0042","operationIds":["op_refund_1"]}']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/is unknown \(undocumented execCode 0042\).*kept pending as intent:.*manual check needed/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->never();
        $mocks['order_class']->shouldReceive('updateOrderState')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.uncertain', $result['message']);
    }

    /**
     * PRE-3627 review (M1): the row is recorded before the call, so that a refund processed by
     * the API but whose answer is lost still counts as refunded. Without it, nothing is sent.
     */
    public function testSendsNoRefundWhenItsPendingRowCannotBeRecorded()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['refund_repository']->shouldReceive('addRefund')->once()->andReturn(false);
        $mocks['payment_service']->shouldReceive('createRefund')->never();
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/not sent: its pending row could not be recorded/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    public function testStillReportsSuccessAndLogsWhenTheRefundIdCannotBeRecorded()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 201, 'body' => '{"operationIds":["op_refund_1"]}']);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->once()->andReturn(false);
        $mocks['logger']->shouldReceive('error')->once()->with($this->notRecordedLog());
        $mocks['order_class']->shouldReceive('updateOrderState')->once()->with($mocks['order'], 15);

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    public function testStillReportsSuccessAndLogsWhenRecordingTheRefundIdThrows()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 201, 'body' => '{"operationIds":["op_refund_1"]}']);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->once()->andThrow(new \Exception('SQL error'));
        $mocks['logger']->shouldReceive('error')->once()->with($this->notRecordedLog());
        $mocks['order_class']->shouldReceive('updateOrderState')->once()->with($mocks['order'], 15);
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    public function testStillReportsSuccessAndLogsWhenTheOrderStateUpdateThrows()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 201, 'body' => '{"operationIds":["op_refund_1"]}']);
        $mocks['refund_repository']->shouldReceive('addRefund')->once()->andReturn(true);
        $mocks['order_class']->shouldReceive('updateOrderState')->once()->andThrow(new \Exception('boom'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/order state could not be updated/'));
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    public function testStillReportsSuccessAndReloadsWhenTheSuccessTemplateCannotBeRendered()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 201, 'body' => '{"operationIds":["op_refund_1"]}']);
        $mocks['refund_repository']->shouldReceive('addRefund')->once()->andReturn(true);
        $mocks['hook_class']->shouldReceive('displayAdminOrderMain')->andThrow(new \Exception('render boom'));
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/order panel could not be rendered/'));

        $result = $action->refundAction(42, 1000);

        $this->assertTrue($result['result']);
        $this->assertSame('', $result['template']);
        $this->assertTrue($result['reload']);
    }

    public function testRejectsANegativeAmount()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['factory']->shouldReceive('create')->never();

        $result = $action->refundAction(42, -100);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.format', $result['message']);
    }

    public function testKeepsTheIntentIdWhenTheResponseBodyIsNotJson()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()
            ->andReturn(['status' => 201, 'body' => '<html>oops</html>']);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/no refund operation id/'));
        $mocks['refund_repository']->shouldReceive('addRefund')->once()
            ->with(\Mockery::pattern('/^intent:/'), 'op_pay', '42', 1000, 'USD')
            ->andReturn(true);
        $mocks['refund_repository']->shouldReceive('bindRefundOperationId')->never();
        $mocks['order_class']->shouldReceive('updateOrderState')->once();

        $this->assertTrue($action->refundAction(42, 1000)['result']);
    }

    /**
     * PRE-3627 review (M1): a timeout (CurlHttpClient's status 0), a 5xx or a 409 may come after
     * the API processed the refund. Reporting a plain failure would invite the merchant to refund
     * again: the row stays pending (still counted as refunded) and the portal must be checked.
     *
     * @dataProvider uncertainFailureProvider
     */
    public function testKeepsTheRefundPendingAndAsksToCheckThePortalWhenItsOutcomeIsUnknown(\Throwable $exception)
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()->andThrow($exception);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/refund of 1000 for order 42 is unknown.*kept pending as intent:.*manual check needed/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->never();
        $mocks['order_class']->shouldReceive('updateOrderState')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.uncertain', $result['message']);
    }

    public function uncertainFailureProvider()
    {
        return [
            'timeout' => [new ApiException('Unified API refund request failed with HTTP status 0.', 0)],
            'server error' => [new ApiException('Unified API refund request failed with HTTP status 502.', 502)],
            'conflict' => [new ApiException('Unified API refund request failed with HTTP status 409.', 409)],
            'unexpected' => [new \TypeError('bad')],
        ];
    }

    /**
     * @dataProvider upcExceptionProvider
     */
    public function testMarksTheRefundFailedAndReleasesTheLockWhenUpcProvesItWasNotProcessed(\Exception $exception)
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()->andThrow($exception);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/refund failed for order 42/'));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->once()->with(\Mockery::pattern('/^intent:/'), 'failed')->andReturn(true);
        $mocks['order_class']->shouldReceive('updateOrderState')->never();
        $mocks['lock']->shouldReceive('release')->once()->with('uhf_refund:42');

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    public function testLogsWhenAFailedRefundCannotBeMarkedFailed()
    {
        [$action, $mocks] = $this->mockAction();
        $mocks['payment_service']->shouldReceive('createRefund')->once()->andThrow(new ApiException('Bad request', 400));
        $mocks['refund_repository']->shouldReceive('updateStatusIfPending')->once()->andReturn(false);
        $mocks['logger']->shouldReceive('error')->once()->with(\Mockery::pattern('/could not be marked failed, its amount stays blocked/'));

        $result = $action->refundAction(42, 1000);

        $this->assertFalse($result['result']);
        $this->assertSame('refund.error.default', $result['message']);
    }

    public function upcExceptionProvider()
    {
        return [
            'api' => [new ApiException('The IP address "10.0.0.1" is not allowed', 403)],
            'not found' => [new PaymentNotFoundException('Unified API has no payment "op_pay".', 404)],
            'amount' => [new RefundAmountException('amount must be positive')],
            'request' => [new InvalidRefundRequestException('orderId must not be empty')],
        ];
    }

    private function notRecordedLog()
    {
        return \Mockery::on(function ($message) {
            return 1 === preg_match('/not recorded locally/', $message)
                && false !== strpos($message, 'order 42')
                && false !== strpos($message, 'op_refund_1')
                && false !== strpos($message, '1000');
        });
    }

    /**
     * Default happy-path wiring: order 42 (cart 6, USD, reference XKBKNABJK, current state 2),
     * paid by operation op_pay for 2900 cents, nothing refunded yet, live mode (refund state 7,
     * partial refund state 15), account id acc_usd configured for USD.
     *
     * @return array{0: UnifiedRefundAction, 1: array<string, mixed>}
     */
    private function mockAction()
    {
        $action = (new \ReflectionClass(UnifiedRefundAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');
        $lock = \Mockery::mock('Lock');
        $payment_repository = \Mockery::mock('PaymentRepository');
        $refund_repository = \Mockery::mock('UpcRefundRepository');
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $validate_adapter = \Mockery::mock('ValidateAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $order_class = \Mockery::mock('OrderClass');
        $translation_class = \Mockery::mock('TranslationClass');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter');
        $payment_validator = \Mockery::mock('PaymentValidator');
        $hook_class = \Mockery::mock('HookClass');

        $order = new \stdClass();
        $order->id = 42;
        $order->id_cart = 6;
        $order->id_currency = 2;
        $order->module = 'payplug';
        $order->reference = 'XKBKNABJK';
        $order->current_state = 2;

        $currency = new \stdClass();
        $currency->iso_code = 'USD';

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);
        $dependencies->shouldReceive('getValidators')->andReturn(['payment' => $payment_validator]);
        $dependencies->hookClass = $hook_class;

        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getValidate')->andReturn($validate_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class);
        $plugin->shouldReceive('getOrderClass')->andReturn($order_class);
        $plugin->shouldReceive('getTranslationClass')->andReturn($translation_class);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')
            ->with('payplug.utilities.service.unified_api_payment_service_factory')
            ->andReturn($factory);
        $module->shouldReceive('getService')
            ->with('payplug.models.repositories.upc_refund')
            ->andReturn($refund_repository);

        $factory->shouldReceive('createLogger')->andReturn($logger);
        $factory->shouldReceive('createLock')->andReturn($lock);
        $factory->shouldReceive('createPaymentRepository')->andReturn($payment_repository);
        $factory->shouldReceive('create')->andReturn($payment_service)->byDefault();

        $translation_class->shouldReceive('getRefundTranslations')->andReturn([
            'error' => [
                'format' => 'refund.error.format',
                'lower' => 'refund.error.lower',
                'upper' => 'refund.error.upper',
                'default' => 'refund.error.default',
                'uncertain' => 'refund.error.uncertain',
            ],
            'success' => 'refund.success',
        ]);

        $order_adapter->shouldReceive('get')->with(42)->andReturn($order);
        $validate_adapter->shouldReceive('validate')->with('isLoadedObject', $order)->andReturn(true);
        $currency_adapter->shouldReceive('get')->with(2)->andReturn($currency);
        $payment_repository->shouldReceive('getPaidByOrderId')->with('42')
            ->andReturn(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'))
            ->byDefault();
        $refund_repository->shouldReceive('getRefundedAmount')->with('42')->andReturn(0)->byDefault();
        $refund_repository->shouldReceive('addRefund')->andReturn(true)->byDefault();
        $refund_repository->shouldReceive('bindRefundOperationId')->andReturn(true)->byDefault();
        $refund_repository->shouldReceive('updateStatusIfPending')->andReturn(true)->byDefault();
        $payment_repository->shouldReceive('getPaymentIdByOperationId')->with('op_pay')->andReturn('pay_1')->byDefault();
        $prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('USD')->andReturn('acc_usd')->byDefault();
        $lock->shouldReceive('acquire')->with('uhf_refund:42', 90)->andReturn(true)->byDefault();
        $lock->shouldReceive('release')->byDefault();
        $payment_validator->shouldReceive('isRefundableAmount')->andReturn(['result' => true])->byDefault();
        $configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('0')->byDefault();
        $configuration_class->shouldReceive('getValue')->with('order_state_refund')->andReturn('7')->byDefault();
        $configuration_class->shouldReceive('getValue')->with('order_state_partial_refund')->andReturn('15')->byDefault();
        $hook_class->shouldReceive('displayAdminOrderMain')->with(['id_order' => 42])->andReturn('<div>panel</div>')->byDefault();
        $logger->shouldReceive('error')->byDefault();
        $logger->shouldReceive('info')->byDefault();

        $action->dependencies = $dependencies;

        return [$action, [
            'order' => $order,
            'factory' => $factory,
            'logger' => $logger,
            'lock' => $lock,
            'payment_repository' => $payment_repository,
            'refund_repository' => $refund_repository,
            'payment_service' => $payment_service,
            'prestashop_adapter' => $prestashop_adapter,
            'payment_validator' => $payment_validator,
            'configuration_class' => $configuration_class,
            'order_class' => $order_class,
            'hook_class' => $hook_class,
        ]];
    }
}
