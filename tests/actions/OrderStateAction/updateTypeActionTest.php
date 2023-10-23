<?php

namespace PayPlug\tests\actions\OrderStateAction;

/**
 * @group unit
 * @group action
 * @group order_state_action
 *
 * @runTestsInSeparateProcesses
 */
class updateTypeActionTest extends BaseOrderStateAction
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
        $this->assertFalse($this->action->updateTypeAction($param));
    }

    public function testWhenWrongIdOrderState()
    {
        $param = [
            'object' => (object) [
                'id' => 'test',
            ],
        ];

        $this->assertFalse($this->action->updateTypeAction($param));
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

        $this->assertFalse($this->action->updateTypeAction($param));
    }

    public function testWhenDeleteOrderState()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
                'deleted' => true,
            ],
        ];

        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 'test',
            ]);

        $this->action
            ->shouldReceive([
                'deleteTypeAction' => true,
            ]);

        $this->assertTrue($this->action->updateTypeAction($param));
    }

    public function testWhenFailDeleteOrderState()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
                'deleted' => true,
            ],
        ];

        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 'test',
            ]);

        $this->action
            ->shouldReceive([
                'deleteTypeAction' => false,
            ]);

        $this->assertFalse($this->action->updateTypeAction($param));
    }

    public function testWhenAddTypeAction()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
                'deleted' => false,
            ],
        ];

        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 'test',
            ]);

        $this->payplug_orderstate_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => [],
            ]);

        $this->action
            ->shouldReceive([
                'addTypeAction' => true,
            ]);

        $this->assertTrue($this->action->updateTypeAction($param));
    }

    public function testWhenUpdateTypeAction()
    {
        $param = [
            'object' => (object) [
                'id' => 42,
                'deleted' => false,
            ],
        ];

        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 'test',
            ]);

        $this->payplug_orderstate_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => [
                    'test' => 'test',
                ],
                'updateByOderState' => true,
            ]);

        $this->assertTrue($this->action->updateTypeAction($param));
    }
}
