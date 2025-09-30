<?php

namespace PayPlug\tests\actions\RequestAction;

use PayPlug\src\actions\RequestAction;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseRequestAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $dependencies;
    public $plugin;
    public $payment_method_class;
    public $payment_method;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';

        $this->plugin = \Mockery::mock('Plugin');
        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_validator = \Mockery::mock('PaymentValidator');

        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->plugin->shouldReceive([
            'getPaymentMethodClass' => $this->payment_method_class,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->action = \Mockery::mock(RequestAction::class)->makePartial();
        $this->action->dependencies = $this->dependencies;
    }
}
