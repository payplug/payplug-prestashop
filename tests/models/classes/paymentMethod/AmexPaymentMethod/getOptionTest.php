<?php

namespace PayPlug\tests\models\classes\paymentMethod\AmexPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group amex_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getOptionTest extends BaseAmexPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption([])['available_test_mode']);
    }
}
