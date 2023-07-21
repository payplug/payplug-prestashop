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
class getPaymentMethodTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $name
     */
    public function testWhenGivenNameIsNotValidStringFormat($name)
    {
        $this->assertSame([], $this->classe->getPaymentMethod($name));
    }

    public function testWhenNoAvailablePaymentMethodsObjectReturn()
    {
        $name = 'standard';
        $this->classe->shouldReceive([
            'getAvailablePaymentMethodsObject' => [],
        ]);
        $this->assertSame([], $this->classe->getPaymentMethod($name));
    }

    public function testWhenPaymentMethodIsNotInAvailablePaymentMethodsObject()
    {
        $name = 'standard';
        $this->classe->shouldReceive([
            'getAvailablePaymentMethodsObject' => [
                'amex' => new \stdClass(),
            ],
        ]);
        $this->assertSame([], $this->classe->getPaymentMethod($name));
    }

    public function testWhenPaymentMethodIsInAvailablePaymentMethodsObject()
    {
        $name = 'standard';
        $this->classe->shouldReceive([
            'getAvailablePaymentMethodsObject' => [
                'standard' => new \stdClass(),
            ],
        ]);
        $this->assertTrue(is_object($this->classe->getPaymentMethod($name)));
    }
}
