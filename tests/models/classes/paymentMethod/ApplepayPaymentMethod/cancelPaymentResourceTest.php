<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group applepay_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class cancelPaymentResourceTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoPaymentRetrieveForContextCart()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
            ]);
        $this->assertSame([
            'result' => false,
            'message' => 'No payment id for given cart id',
        ], $this->classe->cancelPaymentResource());
    }

    public function testWhenPaymentCantBeAborted()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $api_service = \Mockery::mock('ApiService');
        $api_service
            ->shouldReceive([
                'abortPayment' => [
                    'result' => false,
                    'message' => 'Payment can not be aborded',
                ],
            ]);
        $this->plugin
            ->shouldReceive([
                'getApiService' => $api_service,
            ]);
        $this->assertSame([
            'result' => false,
            'message' => 'Payment can not be aborded',
        ], $this->classe->cancelPaymentResource());
    }

    public function testWhenPaymentIsAborted()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $api_service = \Mockery::mock('ApiService');
        $api_service
            ->shouldReceive([
                'abortPayment' => [
                    'result' => true,
                    'resource' => 'PaymentResource',
                ],
            ]);
        $this->plugin
            ->shouldReceive([
                'getApiService' => $api_service,
            ]);
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->classe->cancelPaymentResource());
    }
}
