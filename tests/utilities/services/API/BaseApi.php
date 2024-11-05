<?php

namespace PayPlug\tests\utilities\services\API;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PayPlug\src\utilities\services\API;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;

/**
 * @internal
 * @coversNothing
 */
class BaseApi extends MockeryTestCase
{
    use FormatDataProvider;

    protected $api;
    protected $authentication;
    protected $card;
    protected $dependencies;
    protected $installment_plan;
    protected $oney_simulation;
    protected $payment;
    protected $plugin;
    protected $refund;
    protected $resource_attribute;
    protected $resource_id;
    protected $service;

    public function setUp()
    {
        $this->plugin = Mockery::mock('Plugin');
        $this->plugin->shouldReceive([]);
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->api = \Mockery::mock('alias:Payplug\Payplug');
        $this->authentication = \Mockery::mock('alias:Payplug\Authentication');
        $this->card = \Mockery::mock('alias:Payplug\Card');
        $this->installment_plan = \Mockery::mock('alias:Payplug\InstallmentPlan');
        $this->oney_simulation = \Mockery::mock('alias:Payplug\OneySimulation');
        $this->payment = \Mockery::mock('alias:Payplug\Payment');
        $this->refund = \Mockery::mock('alias:Payplug\Refund');

        $this->resource_attribute = [
            'amount' => 4242,
            'currency' => 'EUR',
            'notification_url' => 'notification_url',
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $this->resource_id = 'pay_azerty12345';
        $this->service = Mockery::mock(API::class, [$this->dependencies])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->service->shouldReceive([
            'checkEnvironment' => true,
            'setEnvironment' => true,
        ]);
    }
}
