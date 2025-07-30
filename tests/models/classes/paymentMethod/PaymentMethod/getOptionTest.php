<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
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
        $this->assertSame([], $this->class->getOption($configuration));
    }

    public function testWhenPaymentMethodHasNoName()
    {
        $configuration = [];
        $this->class->set('name', '');
        $this->assertSame([], $this->class->getOption($configuration));
    }

    public function testWhenDefaultConfigurationIsGettedAsTrue()
    {
        $configuration = [];
        $this->class->set('name', 'standard');

        $this->configuration->shouldReceive('getDefault')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');
        $this->assertTrue($this->class->getOption($configuration)['checked']);
    }

    public function testWhenDefaultConfigurationIsGettedAsFalse()
    {
        $configuration = [];
        $this->class->set('name', 'standard');
        $this->configuration->shouldReceive('getDefault')
            ->with('payment_methods')
            ->andReturn('{"standard":false}');
        $this->assertFalse($this->class->getOption($configuration)['checked']);
    }
}
