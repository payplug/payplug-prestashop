<?php

namespace PayPlug\tests\models\classes\Address;

use PayPlug\src\models\classes\Address;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseAddress extends TestCase
{
    use FormatDataProvider;
    public $dependencies;
    public $address_adapter;
    public $customer_adapter;
    protected $classe;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->address_adapter = \Mockery::mock('AddressAdapter');

        $this->customer_adapter = \Mockery::mock('CustomerAdapter');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                                'getAddress' => $this->address_adapter,
                                'getCustomer' => $this->customer_adapter,
            ]);
        $this->dependencies
            ->shouldReceive([
                                'getPlugin' => $this->plugin,
        ]);
        $this->classe = \Mockery::mock(Address::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
