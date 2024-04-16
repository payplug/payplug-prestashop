<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group installment_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class isValidResourceTest extends BaseInstallmentPaymentMethod
{
    public function testWhenCartInContextIsntAValidObject()
    {
        $this->context->cart = null;
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertFalse($this->classe->isValidResource());
    }

    public function testWhenStoredResourceCantBeFound()
    {
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
            ]);
        $this->assertFalse($this->classe->isValidResource());
    }

    public function testWhenStoredResourceIsExpired()
    {
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'inst_azerty1234',
                    'method' => 'installment',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->validators['payment']
            ->shouldReceive([
                'isTimeoutCachedPayment' => [
                    'result' => false,
                ],
            ]);
        $this->assertFalse($this->classe->isValidResource());
    }

    public function testWhenResourceCantBeRetrieved()
    {
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'inst_azerty1234',
                    'method' => 'installment',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->validators['payment']
            ->shouldReceive([
                'isTimeoutCachedPayment' => [
                    'result' => true,
                ],
            ]);

        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrieveInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies->apiClass = $apiClass;

        $this->assertFalse($this->classe->isValidResource());
    }

    public function testWhenRetrievedResourceHasFailure()
    {
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'inst_azerty1234',
                    'method' => 'installment',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->validators['payment']
            ->shouldReceive([
                'isTimeoutCachedPayment' => [
                    'result' => true,
                ],
            ]);

        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrieveInstallment' => [
                    'result' => true,
                    'resource' => PaymentMock::getInstallment(),
                ],
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'failure' => [
                            'code' => '401',
                            'message' => 'Non authorized',
                        ],
                    ]),
                ],
            ]);
        $this->dependencies->apiClass = $apiClass;

        $this->assertFalse($this->classe->isValidResource());
    }

    public function testWhenRetrievedResourceIsValid()
    {
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'inst_azerty1234',
                    'method' => 'installment',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->validators['payment']
            ->shouldReceive([
                'isTimeoutCachedPayment' => [
                    'result' => true,
                ],
            ]);

        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrieveInstallment' => [
                    'result' => true,
                    'resource' => PaymentMock::getInstallment(),
                ],
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(),
                ],
            ]);
        $this->dependencies->apiClass = $apiClass;

        $this->assertTrue($this->classe->isValidResource());
    }
}
