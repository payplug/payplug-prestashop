<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 */
class isValidMobilePhoneNumberTest extends TestCase
{
    protected $validator;

    public function setUp(): void
    {
        $this->validator = new paymentValidator();
    }

    public function testReturnsTrueForValidFrenchMobileNumber()
    {
        $this->assertSame(
            ['result' => true, 'message' => ''],
            $this->validator->isValidMobilePhoneNumber('0612345678', 'FR')
        );
    }

    public function testReturnsFalseForValidFrenchLandlineNumber()
    {
        $this->assertSame(
            ['result' => false, 'message' => ''],
            $this->validator->isValidMobilePhoneNumber('0123456789', 'FR')
        );
    }

    public function testReturnsFalseWhenPhoneNumberFormatIsInvalid()
    {
        $this->assertSame(
            ['result' => false, 'message' => 'Invalid argument given, $phone_number must be a valid phone number'],
            $this->validator->isValidMobilePhoneNumber('42', 'FR')
        );
    }

    public function testReturnsFalseWhenIsoCodeIsEmpty()
    {
        $this->assertSame(
            ['result' => false, 'message' => 'Invalid argument given, $iso_code must be a non empty string'],
            $this->validator->isValidMobilePhoneNumber('0612345678', '')
        );
    }

    public function testReturnsFalseWhenPhoneNumberIsNotParseable()
    {
        $this->assertSame(
            ['result' => false, 'message' => 'Error, the mobile phone number is not valid'],
            $this->validator->isValidMobilePhoneNumber('0000000000', 'FR')
        );
    }

    /**
     * Documents an intentional behavior tightening from delegating to UPC's
     * PhoneHelper. Area code 212 is a US-only (New York) area code. The old
     * implementation accepted any region sharing the +1 calling code
     * (getRegionCodesForCountryCode), so validating a 212 number against 'CA'
     * used to return true. UPC's PhoneHelper requires an exact region match
     * (getRegionCodeForNumber), so the same call now correctly returns false.
     */
    public function testUsAreaCodeNumberIsRejectedWhenValidatedAgainstAnotherNanpaCountry()
    {
        $this->assertSame(
            ['result' => false, 'message' => 'Error, the mobile phone number is not valid'],
            $this->validator->isValidMobilePhoneNumber('2125550123', 'CA')
        );
    }
}
