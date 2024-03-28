<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group satispay_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseSatispayPaymentMethod
{
    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption([])['available_test_mode']);
    }
}
