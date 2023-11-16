<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getReturnUrlTest extends BaseInstallmentPaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->classe->set('name', '');
        $this->assertSame([], $this->classe->getReturnUrl());
    }

    public function testWhenStoredPaymentCantBeFoundInDataBase()
    {
        $this->classe->set('name', 'installment');
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
            ]);
        $this->assertSame([], $this->classe->getReturnUrl());
    }

    public function testWhenTheResourceCantBeRetrieve()
    {
        $this->classe->set('name', 'installment');
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
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrieveInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies->apiClass = $apiClass;
        $this->assertSame([], $this->classe->getReturnUrl());
    }

    public function testWhenReturnUrlIsReturned()
    {
        $this->classe->set('name', 'installment');
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
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'retrieveInstallment' => [
                    'result' => true,
                    'resource' => PaymentMock::getInstallment(),
                ],
            ]);
        $this->dependencies->apiClass = $apiClass;
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->assertSame(
            [
                'return_url' => 'https://secure-qa.payplug.com/pay/3nfaejGO3m9dyHFIwfsUTR',
                'embedded' => false,
            ],
            $this->classe->getReturnUrl()
        );
    }
}
