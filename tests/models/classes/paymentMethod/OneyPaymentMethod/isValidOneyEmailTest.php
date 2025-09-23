<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class isValidOneyEmailTest extends BaseOneyPaymentMethod
{
    /**
     * Tests invalid Oney email formats using a data provider.
     *
     * @dataProvider invalidEmailFormatDataProvider
     *
     * @param mixed $email the email address to validate
     */
    public function testInValidOneyEmailFormat($email)
    {
        $is_valid = $this->class->isValidOneyEmail($email);
        $this->assertFalse($is_valid['result']);
    }

    /**
     * Tests a valid Oney email address.
     */
    public function testValidOneyEmail()
    {
        $email = 'test.email@payplug.com';
        $is_valid = $this->class->isValidOneyEmail($email);
        $this->assertTrue($is_valid['result']);
    }

    /**
     * Tests Oney email validation for an email containing an invalid '+' character.
     */
    public function testOneyEmaiLengthCharError()
    {
        $email = 'test+email@payplug.com';
        $is_valid = $this->class->isValidOneyEmail($email);
        $this->assertSame('The + character is not valid. Please change your email address (100 characters max).', $is_valid['message']);
    }

    /**
     * Tests Oney email validation for an email exceeding the maximum allowed length.
     */
    public function testOneyEmaiLengthError()
    {
        $email = str_repeat('a', 90) . '@payplug.com';
        $is_valid = $this->class->isValidOneyEmail($email);
        $this->assertSame('Your email address is too long. Please change your email address (100 characters max).', $is_valid['message']);
    }

    /**
     * Tests Oney email validation for an email containing invalid characters.
     */
    public function testOneyEmailCharError()
    {
        $email = 'invalid!test@payplug.com';
        $result = $this->class->isValidOneyEmail($email);
        $this->assertSame('Your email address is not a valid email', $result['message']);
    }
}
