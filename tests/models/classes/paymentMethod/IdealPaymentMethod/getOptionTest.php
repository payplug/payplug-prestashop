<?php

namespace PayPlug\tests\models\classes\paymentMethod\IdealPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseIdealPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption([])['available_test_mode']);
    }
}
