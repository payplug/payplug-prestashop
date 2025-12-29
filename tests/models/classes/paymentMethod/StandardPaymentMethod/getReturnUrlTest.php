<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group standard_payment_method_class
 */
class getReturnUrlTest extends BaseStandardPaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->class->set('name', '');
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

    public function testWhenReturnUrlGeneratedForIntegratedPayment()
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

        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('integrated');

        $props_service = \Mockery::mock('PropsService');
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.service.props')
            ->andReturn($props_service);
        $props_service->shouldReceive([
            'getServerProp' => 'HTTP_USER_AGENT',
        ]);

        $regex_validator = \Mockery::mock('RegexValidator');
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.validator.regex')
            ->andReturn($regex_validator);
        $regex_validator->shouldReceive([
            'isMobileDevice' => [
                'result' => false,
            ],
        ]);

        $this->assertSame(
            [
                'return_url' => 'https://secure-qa.payplug.com/pay/5ktNvd3BNCp6GPcqIZvY9j',
                'resource_id' => 'pay_azerty1234',
                'cart_id' => 1,
                'embedded' => true,
            ],
            $this->class->getReturnUrl()
        );
    }

    public function testWhenReturnUrlIsReturned()
    {
        $this->class->set('name', 'standard');
        $props_service = \Mockery::mock('PropsService');
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.service.props')
            ->andReturn($props_service);
        $props_service->shouldReceive([
            'getServerProp' => 'HTTP_USER_AGENT',
        ]);
        $regex_validator = \Mockery::mock('RegexValidator');
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.validator.regex')
            ->andReturn($regex_validator);
        $regex_validator->shouldReceive([
            'isMobileDevice' => [
                'result' => false,
            ],
        ]);

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

        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->assertSame(
            [
                'return_url' => 'https://secure-qa.payplug.com/pay/5ktNvd3BNCp6GPcqIZvY9j',
                'embedded' => false,
            ],
            $this->class->getReturnUrl()
        );
    }
}
