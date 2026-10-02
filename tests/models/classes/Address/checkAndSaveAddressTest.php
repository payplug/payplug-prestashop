<?php

namespace PayPlug\tests\models\classes\Address;

use PayPlug\tests\mock\AddressMock;

/**
 * @group unit
 * @group class
 * @group address_class
 */
class checkAndSaveAddressTest extends BaseAddress
{
    public $user_address;

    public function setUp(): void
    {
        parent::setUp();

        $this->address_adapter->shouldReceive('get')
            ->andReturn(AddressMock::get())
            ->byDefault();

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
        $result = $this->class->checkAndSaveAddress($user_address, 123, []);

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
        $result = $this->class->checkAndSaveAddress($this->user_address, $customer_id, []);

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
        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $customer_addresses);

        $this->assertEquals(0, $result);
    }

    /**
     * @description  test with invalid bool provider
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $complete_phone_mobile
     */
    public function testWithInvalidCompletePhoneMobile($complete_phone_mobile)
    {
        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, [], $complete_phone_mobile);

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

        $this->address_adapter->shouldReceive(
            [
                'saveAddress' => true,
            ]
        );

        $result = $this->class->checkAndSaveAddress($this->user_address, $customer_id, $customer_addresses);

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

        $result = $this->class->checkAndSaveAddress($this->user_address, $customer_id, $existingAddresses);

        $this->assertEquals($existing_address_id, $result);
    }

    /**
     * @description  test when address provided
     * does not match any of the existing ones
     */
    public function testCheckAndSaveAddressWithNoMatchingAddressCreatesANewOne()
    {
        $existing_addresses = [
            $this->getExistingAddress(['address1' => '456 Avenue', 'phone_mobile' => '']),
        ];
        $this->user_address['phone_mobile'] = '+33612345678';
        $new_address = AddressMock::get();

        $this->address_adapter->shouldReceive('saveAddress')
            ->once()
            ->with(\Mockery::on(function ($address) {
                return '123 Street' === $address->address1
                    && '+33612345678' === $address->phone_mobile
                    && 123 === $address->id_customer;
            }))
            ->andReturn(true);

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses);

        $this->assertEquals($new_address->id, $result);
        $this->address_adapter->mockery_verify();
    }

    /**
     * @description  test when the address provided is the same place
     * but for another person: a new address is saved with the new identity
     */
    public function testCheckAndSaveAddressWithSamePlaceButDifferentIdentityCreatesANewOne()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'firstname' => 'Jane', 'lastname' => 'Smith']),
        ];
        $new_address = AddressMock::get();

        $this->address_adapter->shouldNotReceive('isUsed');
        $this->address_adapter->shouldReceive('saveAddress')
            ->once()
            ->with(\Mockery::on(function ($address) {
                return 'John' === $address->firstname
                    && 'Doe' === $address->lastname
                    && '123 Street' === $address->address1
                    && 123 === $address->id_customer;
            }))
            ->andReturn(true);

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses);

        $this->assertEquals($new_address->id, $result);
        $this->assertNotEquals(7, $result);
        $this->address_adapter->mockery_verify();
    }

    /**
     * @description  test when the matching address has no mobile phone
     * and a mobile phone is given: the existing address is completed
     */
    public function testCheckAndSaveAddressCompletesExistingAddressWithoutPhone()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '']),
        ];
        $this->user_address['phone_mobile'] = '+33612345678';

        $loaded_address = new \stdClass();
        $loaded_address->id = 7;
        $loaded_address->phone_mobile = '';

        $this->address_adapter->shouldReceive('get')
            ->once()
            ->with(7)
            ->andReturn($loaded_address);
        $this->address_adapter->shouldReceive('isUsed')
            ->once()
            ->with($loaded_address)
            ->andReturn(false);
        $this->address_adapter->shouldReceive('saveAddress')
            ->once()
            ->with($loaded_address)
            ->andReturn(true);

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses, true);

        $this->assertEquals(7, $result);
        $this->assertSame('+33612345678', $loaded_address->phone_mobile);
        $this->address_adapter->mockery_verify();
    }

    /**
     * @description  test when the matching address has no mobile phone
     * and a mobile phone is given without asking for the completion (default, non guest flow):
     * the existing address is kept unchanged
     */
    public function testCheckAndSaveAddressDoesNotCompleteExistingAddressByDefault()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '']),
        ];
        $this->user_address['phone_mobile'] = '+33612345678';

        $this->address_adapter->shouldNotReceive('get');
        $this->address_adapter->shouldNotReceive('isUsed');
        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses);

        $this->assertEquals(7, $result);
    }

    /**
     * @description  test when the matching address has no mobile phone
     * but is already used by an order: it is kept unchanged
     */
    public function testCheckAndSaveAddressDoesNotCompleteAddressUsedByAnOrder()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '']),
        ];
        $this->user_address['phone_mobile'] = '+33612345678';

        $loaded_address = new \stdClass();
        $loaded_address->id = 7;
        $loaded_address->phone_mobile = '';

        $this->address_adapter->shouldReceive('get')
            ->once()
            ->with(7)
            ->andReturn($loaded_address);
        $this->address_adapter->shouldReceive('isUsed')
            ->once()
            ->with($loaded_address)
            ->andReturn(2);
        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses, true);

        $this->assertEquals(7, $result);
        $this->assertSame('', $loaded_address->phone_mobile);
        $this->address_adapter->mockery_verify();
    }

    /**
     * @description  test when the matching address can't be loaded:
     * nothing is saved (it would create a new address)
     */
    public function testCheckAndSaveAddressDoesNotSaveWhenExistingAddressCantBeLoaded()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '']),
        ];
        $this->user_address['phone_mobile'] = '+33612345678';

        $not_loaded_address = new \stdClass();
        $not_loaded_address->id = null;

        $this->address_adapter->shouldReceive('get')
            ->with(7)
            ->andReturn($not_loaded_address);
        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses, true);

        $this->assertEquals(7, $result);
    }

    /**
     * @description  test when the matching address has a mobile phone
     * and none is given: the existing address is kept as is
     */
    public function testCheckAndSaveAddressKeepsExistingPhoneWhenNoneGiven()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '+33611111111']),
        ];

        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses, true);

        $this->assertEquals(7, $result);
    }

    /**
     * @description  test when the matching address and the given one
     * have different mobile phones: the existing address is kept as is
     */
    public function testCheckAndSaveAddressKeepsExistingPhoneWhenDifferentOneGiven()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '+33611111111']),
        ];
        $this->user_address['phone_mobile'] = '+33612345678';

        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses, true);

        $this->assertEquals(7, $result);
    }

    /**
     * @description  test when neither the matching address nor the given one
     * have a mobile phone: nothing is saved
     */
    public function testCheckAndSaveAddressWithoutAnyPhone()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '']),
        ];
        $this->user_address['phone_mobile'] = '';

        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses, true);

        $this->assertEquals(7, $result);
    }

    /**
     * @description  test when the matching address and the given one
     * have the same mobile phone: nothing is saved
     */
    public function testCheckAndSaveAddressWithSamePhone()
    {
        $existing_addresses = [
            $this->getExistingAddress(['id_address' => 7, 'phone_mobile' => '+33612345678']),
        ];
        $this->user_address['phone_mobile'] = '+33612345678';

        $this->address_adapter->shouldNotReceive('saveAddress');

        $result = $this->class->checkAndSaveAddress($this->user_address, 123, $existing_addresses, true);

        $this->assertEquals(7, $result);
    }

    /**
     * @param array $override
     *
     * @return array
     */
    private function getExistingAddress($override = [])
    {
        return array_merge([
            'id_address' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'address1' => '123 Street',
            'postcode' => '12345',
            'city' => 'Paris',
            'id_country' => 1,
        ], $override);
    }
}
