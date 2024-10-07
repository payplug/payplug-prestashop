<?php

namespace PayPlug\tests\models\classes\paymentMethod\SofortPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group sofort_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseSofortPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }
}
