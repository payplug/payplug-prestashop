<?php

namespace PayPlug\tests\application\adapter\CustomerAdapter;

use PayPlug\src\application\adapter\CustomerAdapter;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group adapter
 * @group customer_adapter
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class getGuestIdByEmailTest extends TestCase
{
    use FormatDataProvider;

    protected $adapter;
    protected $customer;

    public function setUp(): void
    {
        $this->customer = \Mockery::mock('alias:\Customer');
        $this->adapter = new CustomerAdapter();
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $email
     */
    public function testWhenGivenEmailIsInvalidStringFormat($email)
    {
        $this->customer->shouldNotReceive('getCustomersByEmail');
        $this->assertSame(0, $this->adapter->getGuestIdByEmail($email));
    }

    public function testWhenNoCustomerIsFound()
    {
        $this->customer->shouldReceive('getCustomersByEmail')
            ->with('guest@payplug.com')
            ->andReturn([]);
        $this->assertSame(0, $this->adapter->getGuestIdByEmail('guest@payplug.com'));
    }

    public function testWhenCoreReturnsNonArray()
    {
        $this->customer->shouldReceive('getCustomersByEmail')
            ->andReturn(false);
        $this->assertSame(0, $this->adapter->getGuestIdByEmail('guest@payplug.com'));
    }

    public function testWhenOnlyARegisteredAccountExists()
    {
        $this->customer->shouldReceive('getCustomersByEmail')
            ->andReturn([
                ['id_customer' => '12', 'is_guest' => '0', 'deleted' => '0', 'active' => '1'],
            ]);
        $this->assertSame(0, $this->adapter->getGuestIdByEmail('guest@payplug.com'));
    }

    public function testWhenGuestsAreDeletedOrInactive()
    {
        $this->customer->shouldReceive('getCustomersByEmail')
            ->andReturn([
                ['id_customer' => '12', 'is_guest' => '1', 'deleted' => '1', 'active' => '1'],
                ['id_customer' => '13', 'is_guest' => '1', 'deleted' => '0', 'active' => '0'],
            ]);
        $this->assertSame(0, $this->adapter->getGuestIdByEmail('guest@payplug.com'));
    }

    public function testWhenSeveralGuestsExistTheMostRecentIsReturned()
    {
        $this->customer->shouldReceive('getCustomersByEmail')
            ->andReturn([
                ['id_customer' => '12', 'is_guest' => '1', 'deleted' => '0', 'active' => '1'],
                ['id_customer' => '42', 'is_guest' => '1', 'deleted' => '0', 'active' => '1'],
                ['id_customer' => '50', 'is_guest' => '0', 'deleted' => '0', 'active' => '1'],
                ['id_customer' => '60', 'is_guest' => '1', 'deleted' => '1', 'active' => '1'],
                ['id_customer' => '21', 'is_guest' => '1', 'deleted' => '0', 'active' => '1'],
            ]);
        $this->assertSame(42, $this->adapter->getGuestIdByEmail('guest@payplug.com'));
    }
}
