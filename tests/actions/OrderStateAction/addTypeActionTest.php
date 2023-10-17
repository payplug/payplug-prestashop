<?php

namespace PayPlug\tests\actions\OrderStateAction;

/**
 * @group unit
 * @group action
 * @group order_state_action
 *
 * @runTestsInSeparateProcesses
 */
class addTypeActionTest extends BaseOrderStateAction
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
        $this->assertFalse($this->action->addTypeAction($param));
    }

    public function testWhenWrongIdOrderState()
    {
        $param = [
            'object' => (object) [
                'id' => 'test',
            ],
        ];

        $this->assertFalse($this->action->addTypeAction($param));
    }

    public function testWhenWrongType()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
            ],
        ];

        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);

        $this->assertFalse($this->action->addTypeAction($param));
    }

    public function testWhenFunctionReturnFalse()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
            ],
        ];

        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 'test',
            ]);

        $this->payplug_orderstate_repository
            ->shouldReceive([
                'setOrderState' => false,
            ]);

        $this->assertFalse($this->action->addTypeAction($param));
    }

    public function testWhenFunctionReturnTrue()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
            ],
        ];

        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 'test',
            ]);

        $this->payplug_orderstate_repository
            ->shouldReceive([
                'setOrderState' => true,
            ]);

        $this->assertTrue($this->action->addTypeAction($param));
    }
}
