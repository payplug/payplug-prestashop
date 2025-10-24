<?php

namespace PayPlug\tests\models\classes\paymentMethod\MybankPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group mybank_payment_method_class
 */
class getOptionTest extends BaseMybankPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }
}
