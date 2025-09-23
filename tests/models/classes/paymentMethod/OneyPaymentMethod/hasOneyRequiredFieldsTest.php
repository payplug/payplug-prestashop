<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class hasOneyRequiredFieldsTest extends BaseOneyPaymentMethod
{
    /**
     * Setup test dependencies and default data.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Test that hasOneyRequiredFields returns false for empty or invalid input.
     *
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_data
     */
    public function testReturnsFalseForEmptyInput($payment_data)
    {
        $this->assertFalse($this->class->hasOneyRequiredFields($payment_data));
    }

    /**
     * Test that hasOneyRequiredFields returns true for invalid email in shipping.
     */
    public function testReturnsTrueForInvalidEmail()
    {
        $shipping = [
            'email' => 'invalid-email',
            'mobile_phone_number' => '+33123456789',
            'country' => 'FR',
            'city' => 'Paris',
        ];
        $this->class->shouldReceive('isValidOneyEmail')->andReturn(['result' => false]);
        $this->assertTrue($this->class->hasOneyRequiredFields(['shipping' => $shipping]));
    }

    /**
     * Test that hasOneyRequiredFields returns true for invalid phone number in shipping.
     */
    public function testReturnsTrueForInvalidPhoneNumber()
    {
        $shipping = [
            'email' => 'test@example.com',
            'mobile_phone_number' => 'invalid',
            'country' => 'FR',
            'city' => 'Paris',
        ];
        $this->class->shouldReceive('isValidOneyEmail')->andReturn(['result' => true]);
        $this->validators['payment']->shouldReceive([
            'isPhoneNumber' => [
                'result' => false,
                'message' => 'Invalid argument given, $phone must be a valid phone number format (E.164)',
            ],
        ]);
        $this->assertTrue($this->class->hasOneyRequiredFields(['shipping' => $shipping]));
    }

    /**
     * Test that hasOneyRequiredFields returns true for city name longer than 32 characters.
     */
    public function testReturnsTrueForCityNameTooLong()
    {
        $shipping = [
            'email' => 'test@example.com',
            'mobile_phone_number' => '+33123456789',
            'country' => 'FR',
            'city' => str_repeat('a', 33),
        ];

        $this->class->shouldReceive('isValidOneyEmail')->andReturn(['result' => true]);
        $this->validators['payment']->shouldReceive([
            'isPhoneNumber' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->validators['payment']->shouldReceive([
            'isPhoneNumber' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->assertTrue($this->class->hasOneyRequiredFields(['shipping' => $shipping]));
    }

    /**
     * Test that hasOneyRequiredFields returns false when all required fields in shipping and billing are valid.
     */
    public function testReturnsFalseForAllValidFields()
    {
        $shipping = [
            'email' => 'test@example.com',
            'mobile_phone_number' => '+33123456789',
            'country' => 'FR',
            'city' => 'Paris',
        ];
        $billing = [
            'email' => 'test@example.com',
            'mobile_phone_number' => '+33123456789',
            'country' => 'FR',
            'city' => 'Paris',
        ];
        $payment_data = [
            'shipping' => $shipping,
            'billing' => $billing,
        ];
        $this->class->shouldReceive('isValidOneyEmail')->andReturn(['result' => true]);
        $this->validators['payment']->shouldReceive([
            'isPhoneNumber' => [
                'result' => true,
                'message' => '',
            ],
            'isValidMobilePhoneNumber' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->with('strlen', $payment_data['shipping']['city'], 'UTF-8')
            ->andReturn(5);
        $this->assertFalse($this->class->hasOneyRequiredFields($payment_data));
    }
}
