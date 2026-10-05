<?php

namespace PayPlug\tests\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_state_repository
 */
class isNativeOrOwnOrderStateTest extends BaseOrderStateRepository
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $order_state
     */
    public function testWhenGivenOrderStateIsInvalidObjectFormat($order_state)
    {
        $this->assertFalse($this->repository->isNativeOrOwnOrderState($order_state));
    }

    /**
     * @dataProvider nativeModuleNameDataProvider
     *
     * @param mixed $module_name
     */
    public function testWhenOrderStateIsNative($module_name)
    {
        $this->assertTrue($this->repository->isNativeOrOwnOrderState($this->getOrderState(42, $module_name)));
    }

    public function testWhenOrderStateBelongsToCurrentModule()
    {
        $this->assertTrue($this->repository->isNativeOrOwnOrderState($this->getOrderState(42, 'payplug')));
    }

    public function testWhenOrderStateBelongsToAnotherModule()
    {
        $this->assertFalse($this->repository->isNativeOrOwnOrderState($this->getOrderState(42, 'ps_checkout')));
    }

    public function nativeModuleNameDataProvider()
    {
        yield [''];

        yield [null];
    }
}
