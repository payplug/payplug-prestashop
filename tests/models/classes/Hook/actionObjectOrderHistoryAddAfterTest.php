<?php

namespace PayPlug\tests\models\classes\Hook;

use PayPlug\tests\mock\OrderHistoryMock;
use PayPlug\tests\mock\OrderMock;

/**
 * @group unit
 * @group class
 * @group configuration_class
 */
class actionObjectOrderHistoryAddAfterTest extends BaseHook
{
    private $params;
    private $order_history;

    private $configuration_class;
    private $order_adpater;
    private $order_class;
    private $module;
    private $module_adapter;
    private $hook_action;
    private $payment_repository;

    public function setUp()
    {
        parent::setUp();

        $this->order_history = OrderHistoryMock::get();
        $this->params = [
            'object' => $this->order_history,
        ];

        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->order_adpater = \Mockery::mock('OrderAdapter');
        $this->order_class = \Mockery::mock('OrderClass');
        $this->payment_repository = \Mockery::mock('PaymentRepository');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');

        // Get the hook action service
        $this->module = \Mockery::mock('Module');
        $this->hook_action = \Mockery::mock('HookAction');
        $this->module->shouldReceive([
            'getService' => $this->hook_action,
        ]);
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);

        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration_class,
            'getOrder' => $this->order_adpater,
            'getOrderClass' => $this->order_class,
            'getPaymentRepository' => $this->payment_repository,
            'getModule' => $this->module_adapter,
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $params
     */
    public function testWhenGivenParamsIsInvalidArrayFormat($params)
    {
        $this->assertFalse($this->class->actionObjectOrderHistoryAddAfter($params));
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $order_history
     */
    public function testWhenGivenOrderHistoryIsInvalidObjectFormat($order_history)
    {
        $this->params = [
            'object' => $order_history,
        ];
        $this->assertFalse($this->class->actionObjectOrderHistoryAddAfter($this->params));
    }

    public function testWhenModuleUsageIsNotAllowed()
    {
        $this->configClass->shouldReceive([
            'isAllowed' => false,
        ]);
        $this->assertTrue($this->class->actionObjectOrderHistoryAddAfter($this->params));
    }

    public function testWhenGettedOrderIsNotFromModule()
    {
        $this->configClass->shouldReceive([
            'isAllowed' => true,
        ]);
        $this->order_adpater->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $this->dependencies->name = 'not_the_same_module';

        $this->assertTrue($this->class->actionObjectOrderHistoryAddAfter($this->params));
    }

    public function testWhenOrderStateGivenIsNotTreated()
    {
        $this->configClass->shouldReceive([
            'isAllowed' => true,
        ]);
        $this->order_adpater->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $this->configuration_class->shouldReceive([
            'getValue' => 42,
        ]);
        $this->assertTrue($this->class->actionObjectOrderHistoryAddAfter($this->params));
    }
}
