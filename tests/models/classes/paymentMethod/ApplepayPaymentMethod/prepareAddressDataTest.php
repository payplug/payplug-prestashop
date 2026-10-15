<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 */
class prepareAddressDataTest extends BaseApplepayPaymentMethod
{
    public $address_data;
    protected $tools_adapter;

    public function setUp(): void
    {
        parent::setUp();
        $this->address_data = [
            'givenName' => 'John',
            'familyName' => 'Doe',
            'addressLines' => ['123 Street'],
            'postalCode' => '12345',
            'locality' => 'City',
            'countryCode' => 'FR',
            'emailAddress' => 'john@example.com',
            'phoneNumber' => '0612345678',
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $address_data
     */
    public function TestPrepareAddressDataWhenAddressDataIsNotValid($address_data)
    {
        $this->assertEquals([], $this->class->prepareAddressData($address_data));
    }

    /**
     * @description  Test prepareAddressData method for shipping address.
     */
    public function testPrepareAddressDataForShipping()
    {
        $expected_data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Street',
            'address2' => '',
            'postcode' => '12345',
            'city' => 'City',
            'country' => 'FR',
            'language' => 'fr',
            'email' => 'john@example.com',
            'mobile_phone_number' => '+33612345678',
        ];

        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $param) {
                return strtolower($param);
            });

        $this->country_adapter->shouldReceive([
            'getByIso' => 1,
        ]);

        $this->assertEquals($expected_data, $this->class->prepareAddressData($this->address_data));
    }

    /**
     * @description Test prepareAddressData method for billing address.
     */
    public function testPrepareAddressDataForBilling()
    {
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $param) {
                return strtolower($param);
            });

        $expected_data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Street',
            'address2' => '',
            'postcode' => '12345',
            'city' => 'City',
            'country' => 'FR',
            'language' => 'fr',
            'email' => 'john@example.com',
            'mobile_phone_number' => '+33612345678',
        ];
        $this->assertEquals($expected_data, $this->class->prepareAddressData($this->address_data));
    }

    /**
     * @dataProvider phoneNumberProvider
     *
     * @param string $phone_number
     * @param string $country_code
     * @param string|null $expected
     */
    public function testPhoneNumberIsFormattedOrNull($phone_number, $country_code, $expected)
    {
        $this->address_data['phoneNumber'] = $phone_number;
        $this->address_data['countryCode'] = $country_code;

        $prepared_data = $this->class->prepareAddressData($this->address_data);

        $this->assertArrayHasKey('mobile_phone_number', $prepared_data);
        $this->assertSame($expected, $prepared_data['mobile_phone_number']);
    }

    public function testPhoneNumberKeyIsAbsentWithoutPhoneNumber()
    {
        unset($this->address_data['phoneNumber']);

        $this->assertArrayNotHasKey('mobile_phone_number', $this->class->prepareAddressData($this->address_data));
    }

    public function phoneNumberProvider()
    {
        return [
            'FR mobile on a DE address' => ['+33612345678', 'DE', '+33612345678'],
            'metropolitan national mobile on a MQ address' => ['0612345678', 'MQ', '+33612345678'],
            'invalid number' => ['234234234', 'IT', null],
            'empty number' => ['', 'FR', null],
        ];
    }
}
