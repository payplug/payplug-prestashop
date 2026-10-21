<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class isMobilePhoneNumberSafelyTest extends BaseOneyPaymentMethod
{
    /**
     * @dataProvider mobileProvider
     *
     * @param string $iso_code
     * @param string $phone_number
     */
    public function testReturnsTrueForMobileAcceptedForTheCountry($iso_code, $phone_number)
    {
        $this->assertTrue($this->class->isMobilePhoneNumberSafely($iso_code, $phone_number));
    }

    /**
     * @dataProvider notMobileProvider
     *
     * @param mixed $iso_code
     * @param mixed $phone_number
     */
    public function testReturnsFalseWhenNotAnAcceptedMobile($iso_code, $phone_number)
    {
        $this->assertFalse($this->class->isMobilePhoneNumberSafely($iso_code, $phone_number));
    }

    public function mobileProvider()
    {
        return [
            'FR mobile on a FR address' => ['FR', '0612345678'],
            'FR mobile on a MQ address' => ['MQ', '+33612345678'],
            'metropolitan national mobile on a MQ address' => ['MQ', '07 81 23 45 67'],
            'Martinique mobile on a MQ address' => ['MQ', '0696123456'],
            'shared Guadeloupe mobile on a BL address' => ['BL', '0690123456'],
            'shared Guadeloupe mobile on a MF address' => ['MF', '+590690123456'],
        ];
    }

    public function notMobileProvider()
    {
        return [
            'BE mobile on a FR address' => ['FR', '+32470123456'],
            'FR mobile on a DE address' => ['DE', '+33612345678'],
            'FR landline' => ['FR', '0123456789'],
            'Martinique landline on a MQ address' => ['MQ', '0596301234'],
            'shared landline on a MF address' => ['MF', '0590871234'],
            'invalid number' => ['IT', '234234234'],
            'empty phone' => ['FR', ''],
            'empty iso' => ['', '0612345678'],
            'non string iso' => [false, '0612345678'],
        ];
    }
}
