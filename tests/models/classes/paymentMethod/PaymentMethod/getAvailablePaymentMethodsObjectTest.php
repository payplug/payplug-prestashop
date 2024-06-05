<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getAvailablePaymentMethodsObjectTest extends BasePaymentMethod
{
    public function testWhenNoAvailablePaymentMethodFound()
    {
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $this->assertSame([], $this->classe->getAvailablePaymentMethodsObject());
    }

    public function testWhenAvailablePaymentMethodHasNoAssociatedClass()
    {
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'wrong_payment_method',
            ],
        ]);
        $this->assertSame([], $this->classe->getAvailablePaymentMethodsObject());
    }

    public function testWhenAvailablePaymentMethodHasAssociatedClass()
    {
        $this->classe->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $this->assertTrue(array_key_exists('standard', $this->classe->getAvailablePaymentMethodsObject()));
    }
}
