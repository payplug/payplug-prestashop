<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group satispay_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseSatispayPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption([])['available_test_mode']);
    }
}
