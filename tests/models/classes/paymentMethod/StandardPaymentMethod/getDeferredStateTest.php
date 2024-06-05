<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group standard_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getDeferredStateTest extends BaseStandardPaymentMethod
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $deferred_state
     */
    public function testWhenGivenDeferredStateWithInvalidIntegerFormat($deferred_state)
    {
        $this->assertSame(
            [],
            $this->classe->getDeferredState($deferred_state)
        );
    }

    public function testWhenNoOrderStatesFound()
    {
        $deferred_state = 42;

        $orderClass = \Mockery::mock('OrderState');
        $orderClass->shouldReceive([
            'getOrderStates' => [],
        ]);

        $this->dependencies->orderClass = $orderClass;

        $this->assertSame(
            [
                [
                    'value' => 0,
                    'label' => 'paymentmethods.deferred.states.default',
                    'checked' => false,
                ],
            ],
            $this->classe->getDeferredState($deferred_state)
        );
    }

    public function testWhenOrderStatesAreFoundButNoOneSelected()
    {
        $deferred_state = 4242;

        $orderClass = \Mockery::mock('OrderState');
        $orderClass->shouldReceive([
            'getOrderStates' => [
                [
                    'id_order_state' => 1,
                    'name' => 'Order state 1',
                ],
                [
                    'id_order_state' => 2,
                    'name' => 'Order state 2',
                ],
                [
                    'id_order_state' => 42,
                    'name' => 'Order state 42',
                ],
            ],
        ]);

        $this->dependencies->orderClass = $orderClass;

        $this->assertSame(
            [
                0 => [
                    'value' => 0,
                    'label' => 'paymentmethods.deferred.states.default',
                    'checked' => false,
                ],
                1 => [
                    'value' => 1,
                    'label' => 'paymentmethods.deferred.states.state',
                    'checked' => false,
                    'warning_msg' => 'paymentmethods.deferred.states.alert',
                ],
                2 => [
                    'value' => 2,
                    'label' => 'paymentmethods.deferred.states.state',
                    'checked' => false,
                    'warning_msg' => 'paymentmethods.deferred.states.alert',
                ],
                42 => [
                    'value' => 42,
                    'label' => 'paymentmethods.deferred.states.state',
                    'checked' => false,
                    'warning_msg' => 'paymentmethods.deferred.states.alert',
                ],
            ],
            $this->classe->getDeferredState($deferred_state)
        );
    }

    public function testWhenOrderStatesAreFoundAndTheGivenStateIsSelected()
    {
        $deferred_state = 42;

        $orderClass = \Mockery::mock('OrderState');
        $orderClass->shouldReceive([
            'getOrderStates' => [
                [
                    'id_order_state' => 1,
                    'name' => 'Order state 1',
                ],
                [
                    'id_order_state' => 2,
                    'name' => 'Order state 2',
                ],
                [
                    'id_order_state' => 42,
                    'name' => 'Order state 42',
                ],
            ],
        ]);

        $this->dependencies->orderClass = $orderClass;

        $this->assertSame(
            [
                0 => [
                    'value' => 0,
                    'label' => 'paymentmethods.deferred.states.default',
                    'checked' => false,
                ],
                1 => [
                    'value' => 1,
                    'label' => 'paymentmethods.deferred.states.state',
                    'checked' => false,
                    'warning_msg' => 'paymentmethods.deferred.states.alert',
                ],
                2 => [
                    'value' => 2,
                    'label' => 'paymentmethods.deferred.states.state',
                    'checked' => false,
                    'warning_msg' => 'paymentmethods.deferred.states.alert',
                ],
                42 => [
                    'value' => 42,
                    'label' => 'paymentmethods.deferred.states.state',
                    'checked' => true,
                    'warning_msg' => 'paymentmethods.deferred.states.alert',
                ],
            ],
            $this->classe->getDeferredState($deferred_state)
        );
    }
}
