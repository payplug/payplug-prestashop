<?php

namespace PayPlug\tests\models\classes\paymentMethod\GiropayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group giropay_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getOptionTest extends BaseGiropayPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption([])['available_test_mode']);
    }
}
