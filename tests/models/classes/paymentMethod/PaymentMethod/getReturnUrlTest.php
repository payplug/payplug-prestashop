<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getReturnUrlTest extends BasePaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->assertSame([], $this->class->getReturnUrl());
    }

    public function testWhenStoredPaymentCantBeFoundInDataBase()
    {
        $this->class->set('name', 'standard');
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame([], $this->class->getReturnUrl());
    }

    public function testWhenTheResourceCantBeRetrieved()
    {
        $this->class->set('name', 'standard');
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
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertSame([], $this->class->getReturnUrl());
    }

    public function testWhenReturnUrlIsReturned()
    {
        $this->class->set('name', 'standard');
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
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $resource,
        ]);
        $this->assertSame(
            [
                'return_url' => 'https://secure-qa.payplug.com/pay/5ktNvd3BNCp6GPcqIZvY9j',
                'resource_stored' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'is_live' => true,
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ],
            $this->class->getReturnUrl()
        );
    }
}
