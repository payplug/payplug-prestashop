<?php

namespace PayPlug\tests\models\classes\Order;

use PayPlug\src\models\classes\Order;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseOrder extends TestCase
{
    use FormatDataProvider;

    protected $logger;
    protected $dependencies;
    protected $classe;
    protected $payment_validator;
    protected $plugin;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->logger = \Mockery::mock('Logger');
        $this->payment_validator = \Mockery::mock('PaymentValidator');
        $this->plugin = \Mockery::mock('Plugin');

        $this->logger
            ->shouldReceive([
                'addLog' => true,
            ]);
        $this->plugin
            ->shouldReceive([
                'getLogger' => $this->logger,
            ]);

        $this->dependencies->name = 'payplug';
        $this->dependencies->apiClass = \Mockery::mock('ApiClass');
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => [
                    'payment' => $this->payment_validator,
                ],
            ]);

        $this->classe = \Mockery::mock(Order::class, [$this->dependencies])->makePartial();
    }
}
