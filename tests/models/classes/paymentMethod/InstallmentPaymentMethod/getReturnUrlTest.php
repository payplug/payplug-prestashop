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
class getReturnUrlTest extends BaseInstallmentPaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->class->set('name', '');
        $this->assertSame([], $this->class->getReturnUrl());
    }

    public function testWhenStoredPaymentCantBeFoundInDataBase()
    {
        $this->class->set('name', 'installment');
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame([], $this->class->getReturnUrl());
    }

    public function testWhenTheResourceCantBeRetrieved()
    {
        $this->class->set('name', 'installment');
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'inst_azerty1234',
                'is_live' => true,
                'method' => 'installment',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertSame([], $this->class->getReturnUrl());
    }

    public function testWhenReturnUrlIsReturned()
    {
        $this->class->set('name', 'installment');
        $regex_validator = \Mockery::mock('RegexValidator');
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.validator.regex')
            ->andReturn($regex_validator);
        $regex_validator->shouldReceive([
            'isMobileDevice' => false,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'inst_azerty1234',
                'is_live' => true,
                'method' => 'installment',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(),
            ],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->assertSame(
            [
                'return_url' => 'https://secure-qa.payplug.com/pay/3nfaejGO3m9dyHFIwfsUTR',
                'embedded' => false,
            ],
            $this->class->getReturnUrl()
        );
    }
}
