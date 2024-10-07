<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_classe
 * @group parent_payment_method_classe
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
    public function testWhenGivenNameIsntValidStringFormat($name)
    {
        $this->assertSame([], $this->class->getPaymentMethod($name));
    }

    public function testWhenNoAvailablePaymentMethodsObjectReturn()
    {
        $name = 'standard';
        $this->class->shouldReceive([
            'getAvailablePaymentMethodsObject' => [],
        ]);
        $this->assertSame([], $this->class->getPaymentMethod($name));
    }

    public function testWhenPaymentMethodIsntInAvailablePaymentMethodsObject()
    {
        $name = 'standard';
        $this->class->shouldReceive([
            'getAvailablePaymentMethodsObject' => [
                'amex' => new \stdClass(),
            ],
        ]);
        $this->assertSame([], $this->class->getPaymentMethod($name));
    }

    public function testWhenPaymentMethodIsInAvailablePaymentMethodsObject()
    {
        $name = 'standard';
        $this->class->shouldReceive([
            'getAvailablePaymentMethodsObject' => [
                'standard' => new \stdClass(),
            ],
        ]);
        $this->assertTrue(is_object($this->class->getPaymentMethod($name)));
    }
}
