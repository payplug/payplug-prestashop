<?php

namespace PayPlug\tests\actions\OrderStateAction;

/**
 * @group unit
 * @group action
 * @group order_state_action
 */
class repairPartialRefundStateActionTest extends BaseOrderStateAction
{
    private $configuration;
    private $configuration_class;
    private $order_state_model_repository;
    private $order_state_repository;
    private $order_states = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->dependencies->name = 'payplug';

        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->configuration_class->shouldReceive('getName')
            ->with('order_state_partial_refund')
            ->andReturn('PAYPLUG_ORDER_STATE_PARTIAL_REFUND');
        $this->configuration_class->order_states = [
            'partial_refund' => [
                'name' => ['en' => 'Partially refunded', 'fr' => 'Remboursé partiellement'],
            ],
        ];

        $this->configuration = \Mockery::mock('ConfigurationAdapter');
        $this->order_state_model_repository = \Mockery::mock('OrderStateModelRepository');
        $this->order_state_repository = \Mockery::mock('OrderStateRepository');

        $this->plugin->shouldReceive([
            'getConfiguration' => $this->configuration,
            'getConfigurationClass' => $this->configuration_class,
            'getOrderState' => $this->order_state_repository,
            'getOrderStateRepository' => $this->order_state_model_repository,
        ]);

        // States known by the shop: 31 is the Payplug one, 32 a merchant's custom one, 4 a native one
        $this->order_states = [
            4 => $this->getOrderState(4, ''),
            31 => $this->getOrderState(31, 'payplug'),
            32 => $this->getOrderState(32, ''),
        ];
        $this->order_state_adapter->shouldReceive('get')
            ->andReturnUsing(function ($id) {
                return isset($this->order_states[$id]) ? $this->order_states[$id] : $this->getOrderState(0, '');
            });
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return !empty($object->id);
            });
        $this->order_state_model_repository->shouldReceive('getByName')->andReturn(31)->byDefault();
    }

    public function tearDown(): void
    {
        // Verify the once() / never() expectations
        \Mockery::close();
    }

    public function testWhenTheKeyIsNotStored()
    {
        $this->givenStoredValues([]);
        $this->configuration->shouldNotReceive('updateValue');
        $this->action->shouldNotReceive('installTypeAction');

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenTheStateAlreadyBelongsToPayplug()
    {
        $this->givenStoredValues([$this->row(null, null, 31)]);
        $this->payplug_orderstate_repository->shouldNotReceive('deleteBy');
        $this->configuration->shouldNotReceive('updateValue');
        $this->action->shouldNotReceive('installTypeAction');

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenTheStateIsAMerchantOneTypedByPayplug()
    {
        $this->givenStoredValues([$this->row(null, null, 32)]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn(['type' => 'partial_refund']);
        $this->payplug_orderstate_repository->shouldReceive('deleteBy')->once()->with('id_order_state', 32)->andReturn(true);
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '31', 0, 0)
            ->andReturn(true);
        $this->action->shouldReceive('installTypeAction')->once()->andReturn(true);

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenTheStateIsANativeOneWithItsOwnType()
    {
        $this->givenStoredValues([$this->row(null, null, 4)]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn(['type' => 'nothing']);
        $this->payplug_orderstate_repository->shouldNotReceive('deleteBy');
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '31', 0, 0)
            ->andReturn(true);
        $this->action->shouldReceive('installTypeAction')->once()->andReturn(true);

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenTheStateIsMissing()
    {
        $this->givenStoredValues([$this->row(null, null, 99)]);
        $this->payplug_orderstate_repository->shouldNotReceive('getBy');
        $this->payplug_orderstate_repository->shouldNotReceive('deleteBy');
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '31', 0, 0)
            ->andReturn(true);
        $this->action->shouldReceive('installTypeAction')->once()->andReturn(true);

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenTheTypeCannotBeDeleted()
    {
        $this->givenStoredValues([$this->row(null, null, 32)]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn(['type' => 'refund']);
        $this->payplug_orderstate_repository->shouldReceive('deleteBy')->once()->andReturn(false);
        $this->configuration->shouldNotReceive('updateValue');
        $this->action->shouldNotReceive('installTypeAction');

        $this->assertFalse($this->action->repairPartialRefundStateAction());
    }

    public function testWhenTheKeyCannotBeUpdated()
    {
        $this->givenStoredValues([$this->row(null, null, 32)]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn([]);
        $this->configuration->shouldReceive('updateValue')->once()->andReturn(false);
        $this->action->shouldNotReceive('installTypeAction');

        $this->assertFalse($this->action->repairPartialRefundStateAction());
    }

    public function testWhenSeveralShopsAreStored()
    {
        $this->givenStoredValues([
            $this->row(null, null, 31),
            $this->row(1, 1, 32),
            $this->row(1, 2, 32),
        ]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn(['type' => 'partial_refund']);
        $this->payplug_orderstate_repository->shouldReceive('deleteBy')->once()->with('id_order_state', 32)->andReturn(true);
        $this->order_state_model_repository->shouldReceive('getByName')->once()->andReturn(31);
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '31', 1, 1)
            ->andReturn(true);
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '31', 1, 2)
            ->andReturn(true);
        $this->configuration->shouldNotReceive('updateValue')->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '31', 0, 0);
        $this->action->shouldReceive('installTypeAction')->once()->andReturn(true);

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenThePayplugStateDoesNotExistThenItIsCreated()
    {
        $this->givenStoredValues([$this->row(null, null, 32)]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn([]);
        $this->order_state_model_repository->shouldReceive('getByName')->andReturn([]);
        $this->order_state_repository->shouldReceive('add')
            ->once()
            ->with('partial_refund', $this->configuration_class->order_states['partial_refund'], false)
            ->andReturn(40);
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '40', 0, 0)
            ->andReturn(true);
        $this->action->shouldReceive('installTypeAction')->once()->andReturn(true);

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenTheStateFoundByNameIsNotAPayplugOneThenItIsCreated()
    {
        $this->givenStoredValues([$this->row(null, null, 4)]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn([]);
        $this->order_state_model_repository->shouldReceive('getByName')->andReturn(32);
        $this->order_state_repository->shouldReceive('add')->once()->andReturn(40);
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND', '40', 0, 0)
            ->andReturn(true);
        $this->action->shouldReceive('installTypeAction')->once()->andReturn(true);

        $this->assertTrue($this->action->repairPartialRefundStateAction());
    }

    public function testWhenThePayplugStateCannotBeCreated()
    {
        $this->givenStoredValues([$this->row(null, null, 32)]);
        $this->payplug_orderstate_repository->shouldReceive('getBy')->andReturn([]);
        $this->order_state_model_repository->shouldReceive('getByName')->andReturn([]);
        $this->order_state_repository->shouldReceive('add')->once()->andReturn(false);
        $this->configuration->shouldNotReceive('updateValue');
        $this->action->shouldNotReceive('installTypeAction');

        $this->assertFalse($this->action->repairPartialRefundStateAction());
    }

    private function givenStoredValues(array $rows)
    {
        $this->order_state_model_repository->shouldReceive('getConfigurationsByName')
            ->with('PAYPLUG_ORDER_STATE_PARTIAL_REFUND')
            ->andReturn($rows);
    }

    private function row($id_shop_group, $id_shop, $value)
    {
        return [
            'id_shop_group' => $id_shop_group,
            'id_shop' => $id_shop,
            'value' => (string) $value,
        ];
    }

    private function getOrderState($id, $module_name)
    {
        $order_state = new \stdClass();
        $order_state->id = $id;
        $order_state->deleted = 0;
        $order_state->module_name = $module_name;

        return $order_state;
    }
}
