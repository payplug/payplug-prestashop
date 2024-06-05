<?php

namespace PayPlug\tests\models\classes\Address;

use PayPlug\tests\mock\AddressMock;

/**
 * @group unit
 * @group classes
 * @group address_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class checkAndSaveAddressTest extends BaseAddress
{
    private $user_address;

    public function setUp()
    {
        parent::setUp();

        $this->address_adapter
            ->shouldReceive([
              'get' => AddressMock::get(),
       ]);

        $this->user_address = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'address1' => '123 Street',
            'postcode' => '12345',
            'city' => 'Paris',
            'id_country' => 1,
        ];
    }

    /**
     * @description  test with invalid array provider
     * @dataProvider invalidArrayFormatDataProvider
     *
     * * @param mixed $user_address
     */
    public function testWithInvalidUserAddress($user_address)
    {
        $result = $this->classe->checkAndSaveAddress($user_address, 123, []);

        $this->assertEquals(0, $result);
    }

    /**
     * @description  test with invalid array provider
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * * @param mixed $customer_id
     */
    public function testWithInvalidCustomerId($customer_id)
    {
        $result = $this->classe->checkAndSaveAddress($this->user_address, $customer_id, []);

        $this->assertEquals(0, $result);
    }

    /**
     * @description  test with invalid array provider
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $customer_addresses
     */
    public function testWithInvalidCustomerAddresses($customer_addresses)
    {
        $result = $this->classe->checkAndSaveAddress($this->user_address, 123, $customer_addresses);

        $this->assertEquals(0, $result);
    }

    /**
     * @description  test when address provided
     * does not exist in DB
     */
    public function testCheckAndSaveAddressWithNonExistingAddress()
    {
        $new_address_id = AddressMock::get()->id;
        $customer_id = 1;
        $customer_addresses = [];

        $this->address_adapter
            ->shouldReceive(
                [
                    'saveAddress' => true,
                ]
            );

        $result = $this->classe->checkAndSaveAddress($this->user_address, $customer_id, $customer_addresses);

        // Assert that the method returns the existing address ID
        $this->assertEquals($new_address_id, $result);
    }

    /**
     * @description  test when address provided
     * already exists in DB
     */
    public function testCheckAndSaveAddressWithExistingAddress()
    {
        $existingAddresses = [
            [
                'id_address' => 1,
                'firstname' => 'John',
                'lastname' => 'Doe',
                'address1' => '123 Street',
                'postcode' => '12345',
                'city' => 'Paris',
                'id_country' => 1,
            ],
        ];
        $existing_address_id = AddressMock::get()->id;
        $customer_id = 123;

        $result = $this->classe->checkAndSaveAddress($this->user_address, $customer_id, $existingAddresses);

        $this->assertEquals($existing_address_id, $result);
    }
}
