<?php

namespace PayPlug\tests\actions\OrderStateAction;

/**
 * @group unit
 * @group action
 * @group order_state_action
 *
 * @runTestsInSeparateProcesses
 */
class deleteTypeActionTest extends BaseOrderStateAction
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $param
     */
    public function testWhenGivenPaymentIsInvalidArrayFormat($param)
    {
        $this->assertFalse($this->action->deleteTypeAction($param));
    }

    public function testWhenWrongIdOrderState()
    {
        $param = [
            'object' => (object) [
                'id' => 'test',
            ],
        ];

        $this->assertFalse($this->action->deleteTypeAction($param));
    }

    public function testWhenFunctionReturnFalse()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
            ],
        ];

        $this->payplug_orderstate_repository
            ->shouldReceive([
                'removeByIdOrderState' => false,
            ]);

        $this->assertFalse($this->action->deleteTypeAction($param));
    }

    public function testWhenFunctionReturnTrue()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
            ],
        ];

        $this->payplug_orderstate_repository
            ->shouldReceive([
                'removeByIdOrderState' => true,
            ]);

        $this->assertTrue($this->action->deleteTypeAction($param));
    }
}
