<?php

namespace PayPlug\tests\models\classes\paymentMethod\MybankPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group mybank_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getOptionTest extends BaseMybankPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption([])['available_test_mode']);
    }
}
