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
class getOptionTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $configuration
     */
    public function testWhenGivenConfigurationIsntValidArray($configuration)
    {
        $this->assertSame([], $this->classe->getOption($configuration));
    }

    public function testWhenPaymentMethodHasNoName()
    {
        $configuration = [];
        $this->classe->set('name', '');
        $this->assertSame([], $this->classe->getOption($configuration));
    }

    public function testWhenDefaultConfigurationIsGettedAsTrue()
    {
        $configuration = [];
        $this->classe->set('name', 'standard');

        $this->configuration
            ->shouldReceive('getDefault')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');
        $this->assertTrue($this->classe->getOption($configuration)['checked']);
    }

    public function testWhenDefaultConfigurationIsGettedAsFalse()
    {
        $configuration = [];
        $this->classe->set('name', 'standard');
        $this->configuration
            ->shouldReceive('getDefault')
            ->with('payment_methods')
            ->andReturn('{"standard":false}');
        $this->assertFalse($this->classe->getOption($configuration)['checked']);
    }
}
