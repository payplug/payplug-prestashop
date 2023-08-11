<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionsAvailabilityTest extends BasePaymentMethod
{
    public function testWhenNoPaymentMethodIsAvailable()
    {
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $this->assertSame([], $this->classe->getPaymentOptionsAvailability());
    }

    public function testWhenNoPaymentMethodFoundInDatabase()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{}');

        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $this->assertSame([], $this->classe->getPaymentOptionsAvailability());
    }

    public function testWhenExpectedPaymentMethodNotFoundAvailableMethods()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"amex":true}');

        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $expected = ['standard' => false];
        $this->assertSame($expected, $this->classe->getPaymentOptionsAvailability());
    }

    public function testWhenExpectedPaymentMethodFoundAvailableMethods()
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');

        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $expected = ['standard' => true];
        $this->assertSame($expected, $this->classe->getPaymentOptionsAvailability());
    }
}
