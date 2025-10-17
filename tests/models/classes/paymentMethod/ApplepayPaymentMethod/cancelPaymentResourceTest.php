<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class cancelPaymentResourceTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoPaymentRetrieveForContextCart()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame([
            'result' => false,
            'message' => 'No payment id for given cart id',
        ], $this->class->cancelPaymentResource());
    }

    public function testWhenPaymentCantBeAborted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->class->shouldReceive([
            'abort' => [
                'result' => false,
                'message' => 'Payment can not be aborded',
            ],
        ]);
        $this->assertSame([
            'result' => false,
            'message' => 'Payment can not be aborded',
        ], $this->class->cancelPaymentResource());
    }

    public function testWhenPaymentIsAborted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->class->shouldReceive([
            'abort' => [
                'result' => true,
                'resource' => 'PaymentResource',
            ],
        ]);
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->class->cancelPaymentResource());
    }
}
