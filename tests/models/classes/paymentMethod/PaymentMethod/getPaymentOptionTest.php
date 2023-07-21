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
class getPaymentOptionTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function testWhenGivenPaymentOptionsIsNotValidArray($payment_options)
    {
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenNoNameFoundForPaymentMethod()
    {
        $this->assertSame([], $this->classe->getPaymentOption([]));
    }

    public function testWhenPaymentMethodDoesNotMatchWithCurrentAddressCountry()
    {
        $this->classe->set('name', 'standard');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{"standard":["FR"]}');

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'BE',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertSame([], $this->classe->getPaymentOption([]));
    }

    public function testWhenPaymentMethodMatchWithCurrentAddressCountry()
    {
        $this->classe->set('name', 'standard');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{"standard":["FR"]}');

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertTrue(array_key_exists('standard', $this->classe->getPaymentOption()));
    }

    public function testWhenPaymentMethodHasNoCountryRestriction()
    {
        $this->classe->set('name', 'standard');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertTrue(array_key_exists('standard', $this->classe->getPaymentOption()));
    }
}
