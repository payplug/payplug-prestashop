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
    public function atestWhenGivenIdCartIsInvalidIntegerFormat($order)
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

    public function autoCapturePaymentAction($order = null)
    {
        $this->setParameters();

        if (!is_object($order) || $order->id) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - Invalid argument given, $order must be a non null object.', 'critical');

            return false;
        }

        $payment_methods = json_decode($this->configuration->getValue('payment_methods'), true);
        $can_use_deferred = (bool) $payment_methods['deferred'];

        if (!$can_use_deferred) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - deferred must be active to allow auto capture');

            return true;
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $order->id_cart);

        if ('installment' == $stored_resource['method']) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - auto capture is not compatible with installment plan.');

            return true;
        }

        // Check if resource can be capture
        $retrieve = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method'])
            ->retrieve($stored_resource['resource_id']);
        $resource = $retrieve['resource'];
        $payment_validator = $this->dependencies->getValidators()['payment'];
        $can_be_captured = empty($resource->failure)
            && !$resource->is_paid
            && $payment_validator->isDeferred($resource)['result']
            && !$payment_validator->isExpired($resource)['result'];

        if (!$can_be_captured) {
            $this->logger->addLog('HookAction::autoCapturePaymentAction() - given resource can\'t be captured');

            return true;
        }

        return (bool) $this->dependencies
            ->getPlugin()
            ->getPaymentAction()
            ->captureAction($resource->id, (int) $order->id)['result'];
    }
}
