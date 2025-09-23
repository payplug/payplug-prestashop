<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getOptionCollectionTest extends BasePaymentMethod
{
    use FormatDataProvider;

    public function testWhenNoAvailablePaymentMethodFound()
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $this->assertSame([], $this->class->getOptionCollection());
    }

    public function testWhenAvailablePaymentMethodIsntValidFeature()
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertSame([], $this->class->getOptionCollection());
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment_method
     */
    public function testWhenPaymentMethodGotIsntAnObject($payment_method)
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $payment_method,
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertSame([], $this->class->getOptionCollection());
    }

    public function testWhenOptionIsReturn()
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $this->class,
            'getOption' => new \stdClass(),
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertTrue(array_key_exists('standard', $this->class->getOptionCollection()));
    }
}
