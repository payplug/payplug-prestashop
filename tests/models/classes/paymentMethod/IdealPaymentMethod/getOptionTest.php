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
    public function testWhenGivenOptionIsNotAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption([])['available_test_mode']);
    }
}
