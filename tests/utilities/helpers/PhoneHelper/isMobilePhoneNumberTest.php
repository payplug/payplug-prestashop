<?php

namespace PayPlug\tests\utilities\helpers\PhoneHelper;

use PayPlug\src\utilities\helpers\PhoneHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group phone_helper
 */
class isMobilePhoneNumberTest extends TestCase
{
    /**
     * Regression test: before PRE-3591 the guard clause
     * `if (!is_string($iso_code) || $iso_code)` returned false for ANY
     * non-empty $iso_code, so this always evaluated to false in production.
     */
    public function testReturnsTrueForValidFrenchMobileNumber()
    {
        $this->assertTrue(PhoneHelper::isMobilePhoneNumber('FR', '0612345678'));
    }

    public function testReturnsFalseForValidFrenchLandlineNumber()
    {
        $this->assertFalse(PhoneHelper::isMobilePhoneNumber('FR', '0123456789'));
    }

    public function testReturnsFalseWhenIsoCodeIsEmpty()
    {
        $this->assertFalse(PhoneHelper::isMobilePhoneNumber('', '0612345678'));
    }

    public function testReturnsFalseWhenIsoCodeIsNotAString()
    {
        $this->assertFalse(PhoneHelper::isMobilePhoneNumber(false, '0612345678'));
    }

    public function testReturnsFalseWhenPhoneNumberIsEmpty()
    {
        $this->assertFalse(PhoneHelper::isMobilePhoneNumber('FR', ''));
    }

    public function testReturnsFalseWhenPhoneNumberIsNotParseable()
    {
        $this->assertFalse(PhoneHelper::isMobilePhoneNumber('FR', '0000000000'));
    }

    /**
     * Documents a second, related tightening beyond the guard-clause bug fix:
     * the old code compared against getRegionCodeForCountryCode() (the
     * *primary* region for a calling code — always 'US' for +1), so a real
     * Canadian mobile number checked against 'CA' used to incorrectly return
     * false. UPC's PhoneHelper checks the number's actual specific region, so
     * this now correctly returns true.
     */
    public function testReturnsTrueForValidCanadianMobileNumber()
    {
        $this->assertTrue(PhoneHelper::isMobilePhoneNumber('CA', '4165550123'));
    }
}
