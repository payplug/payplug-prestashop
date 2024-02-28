<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getReturnUrlTest extends BaseStandardPaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->classe->set('name', '');
        $this->assertSame([], $this->classe->getReturnUrl());
    }

    public function testWhenStoredPaymentCantBeFoundInDataBase()
    {
        $this->classe->set('name', 'standard');
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
            ]);
        $this->assertSame([], $this->classe->getReturnUrl());
    }

    public function testWhenTheResourceCantBeRetrieved()
    {
        $this->classe->set('name', 'standard');
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
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies->apiClass = $apiClass;
        $this->assertSame([], $this->classe->getReturnUrl());
    }

    public function testWhenReturnUrlGeneratedForIntegratedPayment()
    {
        $this->classe->set('name', 'standard');
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
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrievePayment' => $resource,
            ]);
        $this->dependencies->apiClass = $apiClass;

        $_SERVER['HTTP_USER_AGENT'] = 'lorem ipsum';

        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('integrated');

        $this->assertSame(
            [
                'return_url' => 'https://secure-qa.payplug.com/pay/5ktNvd3BNCp6GPcqIZvY9j',
                'resource_id' => 'pay_azerty1234',
                'cart_id' => 1,
                'embedded' => true,
            ],
            $this->classe->getReturnUrl()
        );
    }

    public function testWhenReturnUrlIsReturned()
    {
        $this->classe->set('name', 'standard');
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
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrievePayment' => $resource,
            ]);
        $this->dependencies->apiClass = $apiClass;

        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->assertSame(
            [
                'return_url' => 'https://secure-qa.payplug.com/pay/5ktNvd3BNCp6GPcqIZvY9j',
                'embedded' => false,
            ],
            $this->classe->getReturnUrl()
        );
    }
}
