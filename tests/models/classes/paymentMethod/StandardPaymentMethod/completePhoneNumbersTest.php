<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group standard_payment_method_class
 */
class completePhoneNumbersTest extends BaseStandardPaymentMethod
{
    /**
     * @dataProvider phoneNumbersProvider
     *
     * @param mixed $landline
     * @param mixed $mobile
     * @param mixed $expected_landline
     * @param mixed $expected_mobile
     */
    public function testPhoneNumbersAreCompleted($landline, $mobile, $expected_landline, $expected_mobile)
    {
        $address = $this->class->completePhoneNumbers([
            'landline_phone_number' => $landline,
            'mobile_phone_number' => $mobile,
        ]);

        $this->assertSame($expected_landline, $address['landline_phone_number']);
        $this->assertSame($expected_mobile, $address['mobile_phone_number']);
    }

    public function testOtherAddressKeysAreKept()
    {
        $address = $this->class->completePhoneNumbers([
            'email' => 'jdoe@example.com',
            'country' => 'BE',
            'landline_phone_number' => null,
            'mobile_phone_number' => '+32470123456',
        ]);

        $this->assertSame([
            'email' => 'jdoe@example.com',
            'country' => 'BE',
            'landline_phone_number' => '+32470123456',
            'mobile_phone_number' => '+32470123456',
        ], $address);
    }

    public function testMissingPhoneKeysAreSetToNull()
    {
        $address = $this->class->completePhoneNumbers(['country' => 'FR']);

        $this->assertArrayHasKey('landline_phone_number', $address);
        $this->assertArrayHasKey('mobile_phone_number', $address);
        $this->assertNull($address['landline_phone_number']);
        $this->assertNull($address['mobile_phone_number']);
    }

    public function phoneNumbersProvider()
    {
        return [
            'both null' => [null, null, null, null],
            'both empty strings' => ['', '', null, null],
            'landline only' => ['+33123456789', null, '+33123456789', '+33123456789'],
            'mobile only' => [null, '+33612345678', '+33612345678', '+33612345678'],
            'empty landline, mobile set' => ['', '+33612345678', '+33612345678', '+33612345678'],
            'landline set, empty mobile' => ['+33123456789', '', '+33123456789', '+33123456789'],
            'both set' => ['+33123456789', '+33612345678', '+33123456789', '+33612345678'],
        ];
    }
}
