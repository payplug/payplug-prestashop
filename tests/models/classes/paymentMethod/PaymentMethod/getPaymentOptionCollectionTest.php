<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionCollectionTest extends BasePaymentMethod
{
    public function setUp()
    {
        parent::setUp();
        $this->configuration->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    default:
                        return $this->configuration->getDefault($key);
                }
            });
    }

    public function testWhenNoAvailablePaymentMethodFound()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [],
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    public function testWhenNoAvailableOptionFound()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentOptionsAvailability' => [],
        ]);
        $configClass->shouldReceive([
            'isValidFeature' => true,
            'getImgLang' => 'fr',
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    public function testWhenPaymentMethodIsntAllowed()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
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
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment_method
     */
    public function testWhenPaymentMethodObjectIsntValid($payment_method)
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
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
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    public function testWhenOptionIsReturn()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $this->class,
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
        $this->assertTrue(array_key_exists('standard', $this->class->getPaymentOptionCollection()));
    }
}
