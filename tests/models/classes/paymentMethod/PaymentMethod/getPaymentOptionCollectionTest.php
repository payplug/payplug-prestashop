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
class getPaymentOptionCollectionTest extends BasePaymentMethod
{
    public function testWhenNoAvailablePaymentMethodFound()
    {
        $configClass = \Mockery::mock('Config');
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [],
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->classe->getPaymentOptionCollection());
    }

    public function testWhenNoAvailableOptionFound()
    {
        $configClass = \Mockery::mock('Config');
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [],
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->classe->getPaymentOptionCollection());
    }

    public function testWhenPaymentMethodIsNotAllowed()
    {
        $configClass = \Mockery::mock('Config');
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [
                'standard' => true,
            ],
            'isValidFeature' => false,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->classe->getPaymentOptionCollection());
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment_method
     */
    public function testWhenPaymentMethodObjectIsNotValid($payment_method)
    {
        $configClass = \Mockery::mock('Config');
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $payment_method,
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [
                'standard' => true,
            ],
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->classe->getPaymentOptionCollection());
    }

    public function testWhenOptionIsReturn()
    {
        $configClass = \Mockery::mock('Config');
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $this->classe,
            'getPaymentOption' => [
                'standard' => new \stdClass(),
            ],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [
                'standard' => true,
            ],
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertTrue(array_key_exists('standard', $this->classe->getPaymentOptionCollection()));
    }
}
