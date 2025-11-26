<?php

namespace PayPlug\tests\models\classes\paymentMethod\WeroPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group wero_payment_method_class
 */
class getOptionTest extends BaseWeroPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }
}
