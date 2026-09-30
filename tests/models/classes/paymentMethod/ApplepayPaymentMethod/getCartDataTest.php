<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 */
class getCartDataTest extends BaseApplepayPaymentMethod
{
    protected $address_class;
    protected $customer_adapter;
    protected $cart;
    protected $carrier;
    protected $user;
    protected $shipping_data;
    protected $billing_data;
    protected $tmp_address;
    protected $tmp_address_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->carrier = [
            'identifier' => 1,
        ];
        $this->user = [
            'billing' => [
                'givenName' => 'John',
            ],
            'shipping' => [
                'givenName' => 'John',
                'emailAddress' => 'guest@payplug.com',
            ],
        ];
        $this->shipping_data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'guest@payplug.com',
            'mobile_phone_number' => '+33612345678',
            'address1' => 'address name',
            'postcode' => '75000',
            'city' => 'Paris',
            'country' => 'FR',
        ];
        $this->billing_data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'guest@payplug.com',
            'address1' => 'address name',
            'postcode' => '75000',
            'city' => 'Paris',
            'country' => 'FR',
        ];

        $this->payment_method->shouldReceive('prepareAddressData')
            ->andReturnUsing(function ($address, $email = null) {
                return null === $email ? $this->shipping_data : $this->billing_data;
            });

        $this->cart = CartMock::get();
        $this->cart_adapter->shouldReceive([
            'get' => $this->cart,
        ]);
        $this->cart_adapter->shouldReceive('updateAddresses')
            ->andReturn(true)
            ->byDefault();

        $this->country_adapter->shouldReceive([
            'getByIso' => 8,
            'isCountryActiveByCountryId' => true,
        ]);

        // let each test set its own expectations on the address adapter
        $this->address_adapter->byDefault();
        $this->tmp_address_id = 99;
        $this->tmp_address = new \stdClass();
        $this->tmp_address->id = $this->tmp_address_id;
        $this->tmp_address->id_customer = 0;

        $this->customer_adapter = \Mockery::mock('CustomerAdapter');
        $this->address_class = \Mockery::mock('AddressClass');
        $this->plugin->shouldReceive([
            'getCustomer' => $this->customer_adapter,
            'getAddressClass' => $this->address_class,
        ]);

        // non logged visitor
        $this->context->customer = \Mockery::mock('Customer');
        $this->context->customer->shouldReceive([
            'isLogged' => false,
        ]);
        $this->context->cookie->shouldReceive([
            'write' => true,
        ]);

        $this->tools_adapter->shouldReceive('tool')
            ->with('passwdGen', 32, 'ALPHANUMERIC')
            ->andReturn('password');

        $this->class->shouldReceive([
            'getRequest' => [
                'total' => [
                    'amount' => 42.42,
                ],
            ],
        ]);

        $this->setClassProperty('context', $this->context);
        $this->setClassProperty('country_adapter', $this->country_adapter);
        $this->setClassProperty('tools', $this->tools_adapter);
    }

    public function tearDown(): void
    {
        // verify the call count expectations (once, twice, never) set on the adapters
        foreach ([$this->customer_adapter, $this->address_class, $this->cart_adapter, $this->address_adapter] as $mock) {
            $mock->mockery_verify();
        }
        $this->addToAssertionCount(4);
    }

    public function testWhenCarrierIsNotAvailableOnlyTheTemporaryAddressIsCreated()
    {
        $this->expectTemporaryAddress();
        $this->cart_adapter->shouldReceive('isCarrierInRange')
            ->once()
            ->with(1, 5)
            ->andReturn(false);
        $this->carrier_adapter->shouldReceive([
            'checkCarrierZone' => true,
        ]);

        // only the temporary address is created (any other checkAndSaveAddress call wouldn't match)
        $this->customer_adapter->shouldNotReceive('getGuestIdByEmail');
        $this->customer_adapter->shouldNotReceive('get');
        $this->customer_adapter->shouldNotReceive('add');
        $this->cart_adapter->shouldNotReceive('updateAddresses');

        $this->assertSame([
            'result' => false,
            'message' => 'Given carrier is not available for this delivery address',
        ], $this->callGetCartData());
    }

    public function testWhenCarrierIsNotInZoneOnlyTheTemporaryAddressIsCreated()
    {
        $this->expectTemporaryAddress();
        $this->cart_adapter->shouldReceive([
            'isCarrierInRange' => true,
        ]);
        $this->carrier_adapter->shouldReceive('checkCarrierZone')
            ->once()
            ->with(1, 5)
            ->andReturn(false);

        $this->customer_adapter->shouldNotReceive('add');
        $this->cart_adapter->shouldNotReceive('updateAddresses');

        $this->assertSame([
            'result' => false,
            'message' => 'Given carrier is not available for this delivery address',
        ], $this->callGetCartData());
    }

    public function testWhenTemporaryAddressCantBeCreated()
    {
        $this->address_class->shouldReceive('checkAndSaveAddress')
            ->once()
            ->with($this->getExpectedShippingAddress())
            ->andReturn(0);
        $this->address_adapter->shouldNotReceive('getZoneById');
        $this->address_adapter->shouldNotReceive('get');
        $this->address_adapter->shouldNotReceive('delete');
        $this->cart_adapter->shouldNotReceive('isCarrierInRange');
        $this->carrier_adapter->shouldNotReceive('checkCarrierZone');
        $this->customer_adapter->shouldNotReceive('getGuestIdByEmail');
        $this->customer_adapter->shouldNotReceive('add');
        $this->cart_adapter->shouldNotReceive('updateAddresses');

        $this->assertSame([
            'result' => false,
            'message' => 'Given carrier is not available for this delivery address',
        ], $this->callGetCartData());
    }

    public function testWhenTemporaryAddressIsLinkedToACustomerItIsNotDeleted()
    {
        $this->expectTemporaryAddress(false);
        $this->tmp_address->id_customer = 12;
        $this->cart_adapter->shouldReceive([
            'isCarrierInRange' => false,
        ]);
        $this->carrier_adapter->shouldReceive([
            'checkCarrierZone' => true,
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'Given carrier is not available for this delivery address',
        ], $this->callGetCartData());
    }

    public function testWhenGuestAlreadyExistsItIsReusedWithItsAddresses()
    {
        $this->setAvailableCarrier();

        $guest_id = 42;
        $guest = new \stdClass();
        $guest->id = $guest_id;
        $guest_addresses = [
            [
                'id_address' => 7,
                'firstname' => 'John',
            ],
        ];

        $this->customer_adapter->shouldReceive('getGuestIdByEmail')
            ->once()
            ->with('guest@payplug.com')
            ->andReturn($guest_id);
        $this->customer_adapter->shouldReceive('get')
            ->once()
            ->with($guest_id)
            ->andReturn($guest);
        $this->customer_adapter->shouldReceive('getAddresses')
            ->once()
            ->with($guest_id, 1)
            ->andReturn($guest_addresses);
        $this->customer_adapter->shouldNotReceive('add');

        // billing address is identical to the shipping one (phone excepted): a single address is checked
        $this->address_class->shouldReceive('checkAndSaveAddress')
            ->once()
            ->with(\Mockery::on(function ($address) {
                return '+33612345678' === $address['phone_mobile'];
            }), $guest_id, $guest_addresses)
            ->andReturn(7);
        $this->cart_adapter->shouldReceive('updateAddresses')
            ->once()
            ->with($this->cart, 7, 7);

        $result = $this->callGetCartData();

        $this->assertTrue($result['result']);
        $this->assertSame($guest_id, $this->cart->id_customer);
        $this->assertSame($guest_id, $this->context->cookie->id_customer);
    }

    public function testWhenGuestAlreadyExistsWithDifferentBillingAddressBothAddressesUseGuestAddresses()
    {
        $this->setAvailableCarrier();
        $this->billing_data['address1'] = 'another address';

        $guest_id = 42;
        $guest = new \stdClass();
        $guest->id = $guest_id;
        $guest_addresses = [
            [
                'id_address' => 7,
            ],
        ];

        $this->customer_adapter->shouldReceive([
            'getGuestIdByEmail' => $guest_id,
            'getAddresses' => $guest_addresses,
        ]);
        $this->customer_adapter->shouldReceive('get')
            ->with($guest_id)
            ->andReturn($guest);
        $this->customer_adapter->shouldNotReceive('add');

        // billing address differs from the shipping one: both are checked
        $this->address_class->shouldReceive('checkAndSaveAddress')
            ->twice()
            ->with(\Mockery::type('array'), $guest_id, $guest_addresses)
            ->andReturn(7, 8);
        $this->cart_adapter->shouldReceive('updateAddresses')
            ->once()
            ->with($this->cart, 7, 8);

        $result = $this->callGetCartData();

        $this->assertTrue($result['result']);
    }

    public function testWhenNoGuestExistsANewGuestIsCreated()
    {
        $this->setAvailableCarrier();

        // e.g. only a registered account exists for this email: it is ignored
        $new_guest = new \stdClass();
        $new_guest->id = 0;

        $this->customer_adapter->shouldReceive('getGuestIdByEmail')
            ->once()
            ->with('guest@payplug.com')
            ->andReturn(0);
        $this->customer_adapter->shouldReceive('get')
            ->once()
            ->with(0)
            ->andReturn($new_guest);
        $this->customer_adapter->shouldNotReceive('getAddresses');
        $this->customer_adapter->shouldReceive('add')
            ->once()
            ->with($new_guest)
            ->andReturnUsing(function ($customer) {
                $customer->id = 43;

                return true;
            });

        // identical billing address: the shipping address is reused for billing
        $this->address_class->shouldReceive('checkAndSaveAddress')
            ->once()
            ->with(\Mockery::type('array'), 43, [])
            ->andReturn(9);
        $this->cart_adapter->shouldReceive('updateAddresses')
            ->once()
            ->with($this->cart, 9, 9);

        $result = $this->callGetCartData();

        $this->assertTrue($result['result']);
        $this->assertTrue($new_guest->is_guest);
        $this->assertSame('guest@payplug.com', $new_guest->email);
        $this->assertSame(43, $this->cart->id_customer);
    }

    public function testWhenNewGuestCantBeCreated()
    {
        $this->setAvailableCarrier();

        $this->customer_adapter->shouldReceive([
            'getGuestIdByEmail' => 0,
            'get' => new \stdClass(),
            'add' => false,
        ]);
        $this->address_class->shouldNotReceive('checkAndSaveAddress');

        $this->assertSame([
            'result' => false,
            'message' => 'Guest customer can\'t be created',
        ], $this->callGetCartData());
    }

    /**
     * @description expect the temporary address used to compute the delivery zone
     * to be created, used and (by default) deleted
     *
     * @param bool $deleted
     */
    private function expectTemporaryAddress($deleted = true)
    {
        $this->address_class->shouldReceive('checkAndSaveAddress')
            ->once()
            ->with($this->getExpectedShippingAddress())
            ->andReturn($this->tmp_address_id);
        $this->address_adapter->shouldReceive('getZoneById')
            ->once()
            ->with($this->tmp_address_id)
            ->andReturn(5);
        $this->address_adapter->shouldReceive('get')
            ->once()
            ->with($this->tmp_address_id)
            ->andReturn($this->tmp_address);
        if ($deleted) {
            $this->address_adapter->shouldReceive('delete')
                ->once()
                ->with($this->tmp_address)
                ->andReturn(true);
        } else {
            $this->address_adapter->shouldNotReceive('delete');
        }
    }

    /**
     * @return array
     */
    private function getExpectedShippingAddress()
    {
        return [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'address1' => 'address name',
            'address2' => '',
            'postcode' => '75000',
            'city' => 'Paris',
            'id_country' => 8,
            'phone_mobile' => '+33612345678',
        ];
    }

    private function setAvailableCarrier()
    {
        $this->expectTemporaryAddress();
        $this->cart_adapter->shouldReceive([
            'isCarrierInRange' => true,
        ]);
        $this->carrier_adapter->shouldReceive([
            'checkCarrierZone' => true,
        ]);
    }

    private function setClassProperty($name, $value)
    {
        $property = new \ReflectionProperty(PaymentMethod::class, $name);
        $property->setAccessible(true);
        $property->setValue($this->class, $value);
    }

    private function callGetCartData()
    {
        $method = (new \ReflectionClass($this->class))->getMethod('getCartData');
        $method->setAccessible(true);

        return $method->invoke($this->class, 'cart', $this->carrier, $this->user);
    }
}
