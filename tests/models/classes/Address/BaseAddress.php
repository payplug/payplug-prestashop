<?php

namespace PayPlug\tests\models\classes\Address;

use PayPlug\src\models\classes\Address;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseAddress extends TestCase
{
    use FormatDataProvider;
    protected $dependencies;
    protected $address_adapter;
    protected $customer_adapter;
    protected $class;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->address_adapter = \Mockery::mock('AddressAdapter');

        $this->customer_adapter = \Mockery::mock('CustomerAdapter');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getAddress' => $this->address_adapter,
            'getCustomer' => $this->customer_adapter,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);
        $this->class = \Mockery::mock(Address::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
