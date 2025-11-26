<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group satispay_payment_method_class
 */
class getPaymentTabTest extends BaseSatispayPaymentMethod
{
    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => [],
        ]);
        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenPaymentTabIsReturned()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);
        $expected_tab = $this->default_payment_tab;
        $expected_tab['payment_method'] = 'satispay';
        unset($expected_tab['force_3ds'], $expected_tab['allow_save_card']);

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }
}
