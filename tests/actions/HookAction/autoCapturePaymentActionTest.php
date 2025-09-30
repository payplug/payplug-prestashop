<?php

namespace PayPlug\tests\actions\HookAction;

use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group hook_action
 *
 * @runTestsInSeparateProcesses
 */
class autoCapturePaymentActionTest extends BaseHookAction
{
    private $order;
    private $payment_method_class;
    private $payment_method;
    private $payment_validator;
    private $stored_resource;

    public function setUp()
    {
        parent::setUp();

        $this->order = OrderMock::get();

        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->stored_resource = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];

        $this->payment_validator = \Mockery::mock('PaymentValidator');
        $this->dependencies->shouldReceive([
            'getValidators' => [
                'payment' => $this->payment_validator,
            ],
        ]);
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->plugin->shouldReceive([
            'getPaymentMethodClass' => $this->payment_method_class,
        ]);
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $order
     */
    public function testWhenGivenIdCartIsInvalidIntegerFormat($order)
    {
        $this->assertFalse($this->action->autoCapturePaymentAction($order));
    }

    public function testWhenDeferredCantBeUsed()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":false}');
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }

    public function testWhenStoredResourceIsFromInstallmentPlan()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":true}');
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'method' => 'installment',
            ],
        ]);
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }

    public function testWhenRetrieveResourceHasFailure()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":true}');
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['failure' => ['message' => 'error']]),
            ],
        ]);
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }

    public function testWhenRetrieveResourceIsPaid()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":true}');
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => false]),
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => false,
            ],
        ]);
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }

    public function testWhenRetrieveResourceIsNotDeferred()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":true}');
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true]),
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => false,
            ],
        ]);
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }

    public function testWhenRetrieveResourceIsExpired()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":true}');
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true]),
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => true,
            ],
            'isExpired' => [
                'result' => true,
            ],
        ]);
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }

    public function testWhenRetrieveResourceCantBeCaptured()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":true}');
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true]),
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => true,
            ],
            'isExpired' => [
                'result' => false,
            ],
        ]);
        $this->payment_action->shouldReceive([
            'captureAction' => [
                'result' => false,
            ],
        ]);
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }

    public function testWhenRetrieveResourceIsCaptured()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"deferred":true}');
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true]),
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => true,
            ],
            'isExpired' => [
                'result' => false,
            ],
        ]);
        $this->payment_action->shouldReceive([
            'captureAction' => [
                'result' => true,
            ],
        ]);
        $this->assertTrue($this->action->autoCapturePaymentAction($this->order));
    }
}
