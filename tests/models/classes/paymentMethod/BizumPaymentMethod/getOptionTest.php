<?php

namespace PayPlug\tests\models\classes\paymentMethod\BizumPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group bizum_payment_method_class
 */
class getOptionTest extends BaseBizumPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }
}
