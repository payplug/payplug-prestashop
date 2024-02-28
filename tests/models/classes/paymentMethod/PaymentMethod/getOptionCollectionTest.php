<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOptionCollectionTest extends BasePaymentMethod
{
    use FormatDataProvider;

    public function testWhenNoAvailablePaymentMethodFound()
    {
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $this->assertSame([], $this->classe->getOptionCollection());
    }

    public function testWhenAvailablePaymentMethodIsntValidFeature()
    {
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertSame([], $this->classe->getOptionCollection());
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment_method
     */
    public function testWhenPaymentMethodGettedIsntAnObject($payment_method)
    {
        $this->classe->shouldReceive([
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

        $this->assertSame([], $this->classe->getOptionCollection());
    }

    public function testWhenOptionIsReturn()
    {
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $this->classe,
            'getOption' => new \stdClass(),
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertTrue(array_key_exists('standard', $this->classe->getOptionCollection()));
    }
}
