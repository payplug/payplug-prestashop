<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group applepay_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class prepareAddressDataTest extends BaseApplepayPaymentMethod
{
    protected $tools_adapter;
    private $address_data;

    public function setUp()
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
            'phoneNumber' => '1234567890',
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $address_data
     */
    public function TestPrepareAddressDataWhenAddressDataIsNotValid($address_data)
    {
        $this->assertEquals([], $this->classe->prepareAddressData($address_data));
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
            'postcode' => '12345',
            'city' => 'City',
            'country' => 'FR',
            'language' => 'fr',
            'email' => 'john@example.com',
            'mobile_phone_number' => '1234567890',
        ];

        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $param) {
                return strtolower($param);
            });
        $config_class = \Mockery::mock('ConfigClass');

        $config_class->shouldReceive('formatPhoneNumber')->andReturn('1234567890');

        $this->dependencies->configClass = $config_class;

        $this->country_adapter->shouldReceive([
               'getByIso' => 1,
        ]);

        $this->assertEquals($expected_data, $this->classe->prepareAddressData($this->address_data));
    }

    /**
     * @description Test prepareAddressData method for billing address.
     */
    public function testPrepareAddressDataForBilling()
    {
        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $param) {
                return strtolower($param);
            });
        $config_class = \Mockery::mock('ConfigClass');

        $config_class->shouldReceive('formatPhoneNumber')->andReturn('0657789067');

        $this->dependencies->configClass = $config_class;

        $this->country_adapter->shouldReceive([
                                          'getByIso' => 1,
                                      ]);
        $expected_data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Street',
            'postcode' => '12345',
            'city' => 'City',
            'country' => 'FR',
            'language' => 'fr',
            'email' => 'john@example.com',
        ];
        $prepared_data = $this->classe->prepareAddressData($this->address_data, null);
        $this->assertEquals($expected_data, $prepared_data);
        $this->assertArrayNotHasKey('mobile_phone_number', $prepared_data);
    }
}
