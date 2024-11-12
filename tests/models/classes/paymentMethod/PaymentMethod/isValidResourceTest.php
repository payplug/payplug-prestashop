<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_classe
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class isValidResourceTest extends BasePaymentMethod
{
    public function testWhenCartInContextIsntAValidObject()
    {
        $this->context->cart = null;
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertFalse($this->class->isValidResource());
    }

    public function testWhenStoredResourceCantBeFound()
    {
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertFalse($this->class->isValidResource());
    }

    public function testWhenStoredResourceIsExpired()
    {
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
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
        $this->validators['payment']->shouldReceive([
            'isTimeoutCachedPayment' => [
                'result' => false,
            ],
        ]);
        $this->assertFalse($this->class->isValidResource());
    }

    public function testWhenResourceCantBeRetrieved()
    {
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
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
        $this->validators['payment']->shouldReceive([
            'isTimeoutCachedPayment' => [
                'result' => true,
            ],
        ]);

        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);

        $this->assertFalse($this->class->isValidResource());
    }

    public function testWhenRetrievedResourceHasFailure()
    {
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
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
        $this->validators['payment']->shouldReceive([
            'isTimeoutCachedPayment' => [
                'result' => true,
            ],
        ]);

        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard([
                'failure' => [
                    'code' => '401',
                    'message' => 'Non authorized',
                ],
            ]),
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $resource,
        ]);

        $this->assertFalse($this->class->isValidResource());
    }

    public function testWhenRetrievedResourceIsValid()
    {
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
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
        $this->validators['payment']->shouldReceive([
            'isTimeoutCachedPayment' => [
                'result' => true,
            ],
        ]);

        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $resource,
        ]);

        $this->assertTrue($this->class->isValidResource());
    }
}
