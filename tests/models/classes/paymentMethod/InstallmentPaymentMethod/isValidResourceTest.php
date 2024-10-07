<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class isValidResourceTest extends BaseInstallmentPaymentMethod
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
                'resource_id' => 'inst_azerty1234',
                'method' => 'installment',
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
                'resource_id' => 'inst_azerty1234',
                'method' => 'installment',
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

        $this->class->shouldReceive([
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
                'resource_id' => 'inst_azerty1234',
                'method' => 'installment',
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

        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(),
                'schedule' => [
                    [
                        'amount' => 42,
                        'date' => '1970-01-01 00:00:00',
                        'resource' => PaymentMock::getStandard([
                            'failure' => [
                                'code' => '401',
                                'message' => 'Non authorized',
                            ],
                        ]),
                    ],
                ],
            ],
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
                'resource_id' => 'inst_azerty1234',
                'method' => 'installment',
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

        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(),
                'schedule' => [
                    [
                        'amount' => 42,
                        'date' => '1970-01-01 00:00:00',
                        'resource' => PaymentMock::getStandard(),
                    ],
                ],
            ],
        ]);

        $this->assertTrue($this->class->isValidResource());
    }
}
