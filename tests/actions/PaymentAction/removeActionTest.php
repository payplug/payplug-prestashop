<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class removeActionTest extends BasePaymentAction
{
    private $resource_id;
    private $cancellable;
    private $stored_resource;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'pay_azerty';
        $this->cancellable = true;
        $this->stored_resource = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
        ]);
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsntValidString($resource_id)
    {
        $this->assertFalse(
            $this->action->removeAction($resource_id, $this->cancellable)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $cancellable
     */
    public function testWhenGivenCancellableIsntValidString($cancellable)
    {
        $this->assertFalse(
            $this->action->removeAction($this->resource_id, $cancellable)
        );
    }

    public function testWhenNoStoredPaymentCantBeGetted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertFalse(
            $this->action->removeAction($this->resource_id, $this->cancellable)
        );
    }

    public function testWhenResourceCantBeRetrieved()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertFalse(
            $this->action->removeAction($this->resource_id, $this->cancellable)
        );
    }

    public function testWhenRetrievedResourceCantBeAborted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $resource,
            'abort' => [
                'result' => false,
            ],
        ]);
        $this->assertFalse(
            $this->action->removeAction($this->resource_id, $this->cancellable)
        );
    }

    public function testWhenRetrievedResourceCantBeRemoveFromDataBase()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
            'deleteBy' => false,
        ]);
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $resource,
            'abort' => $resource,
        ]);

        $this->assertFalse(
            $this->action->removeAction($this->resource_id, $this->cancellable)
        );
    }

    public function testWhenRetrievedResourceIsRemove()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
            'deleteBy' => true,
        ]);
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $resource,
            'abort' => $resource,
        ]);
        $this->assertTrue(
            $this->action->removeAction($this->resource_id, $this->cancellable)
        );
    }
}
