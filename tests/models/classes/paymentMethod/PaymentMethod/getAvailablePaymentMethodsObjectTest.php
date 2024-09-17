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
class getAvailablePaymentMethodsObjectTest extends BasePaymentMethod
{
    public function testWhenNoAvailablePaymentMethodFound()
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $this->assertSame([], $this->class->getAvailablePaymentMethodsObject());
    }

    public function testWhenAvailablePaymentMethodHasNoAssociatedClass()
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'wrong_payment_method',
            ],
        ]);
        $this->assertSame([], $this->class->getAvailablePaymentMethodsObject());
    }

    public function testWhenAvailablePaymentMethodHasAssociatedClass()
    {
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $this->assertTrue(array_key_exists('standard', $this->class->getAvailablePaymentMethodsObject()));
    }
}
