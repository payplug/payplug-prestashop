<?php

namespace PayPlug\tests\models\classes\paymentMethod\AmexPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group amex_payment_method_class
 */
class getOptionTest extends BaseAmexPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }
}
