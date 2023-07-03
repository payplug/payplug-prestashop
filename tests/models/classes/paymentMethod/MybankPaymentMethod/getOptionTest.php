<?php

namespace PayPlug\tests\models\classes\paymentMethod\MybankPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseMybankPaymentMethod
{
    public function testWhenGivenOptionIsNotAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption([])['available_test_mode']);
    }
}
