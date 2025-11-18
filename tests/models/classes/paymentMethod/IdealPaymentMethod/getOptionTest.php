<?php

namespace PayPlug\tests\models\classes\paymentMethod\IdealPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group ideal_payment_method_class
 */
class getOptionTest extends BaseIdealPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }
}
