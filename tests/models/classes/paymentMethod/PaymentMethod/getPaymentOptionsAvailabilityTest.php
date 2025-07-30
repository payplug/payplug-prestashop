<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 */
class getPaymentOptionsAvailabilityTest extends BasePaymentMethod
{
    public function testWhenNoPaymentMethodIsAvailable()
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $this->assertSame([], $this->class->getPaymentOptionsAvailability());
    }

    public function testWhenNoPaymentMethodFoundInDatabase()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{}');

        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $this->assertSame([], $this->class->getPaymentOptionsAvailability());
    }

    public function testWhenExpectedPaymentMethodNotFoundAvailableMethods()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"amex":true}');

        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $expected = ['standard' => false];
        $this->assertSame($expected, $this->class->getPaymentOptionsAvailability());
    }

    public function testWhenExpectedPaymentMethodFoundAvailableMethods()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');

        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $expected = ['standard' => true];
        $this->assertSame($expected, $this->class->getPaymentOptionsAvailability());
    }
}
