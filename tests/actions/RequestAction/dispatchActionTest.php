<?php

namespace PayPlug\tests\actions\RequestAction;

/**
 * @group unit
 * @group action
 * @group request_action
 */
class dispatchActionTest extends BaseRequestAction
{
    public $method;
    public $parameters;

    public function setUp()
    {
        parent::setUp();
        $this->method = 'triggerMethod';
        $this->parameters = [
            'key' => 'value',
        ];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method
     */
    public function testWhenGivenMethodIsNotValidString($method)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $method must be a non empty string.',
            ],
            $this->action->dispatchAction($method, $this->parameters)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsNotValidArray($parameters)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $parameters must be a valid array.',
            ],
            $this->action->dispatchAction($this->method, $parameters)
        );
    }

    public function testWhenGivenMethodDoesNotExist()
    {
        try {
            $result = $this->action->dispatchAction('triggerMethod', []);
            $this->assertSame(
                [
                    'result' => false,
                    'message' => 'Method not found in object RequestAction',
                ],
                $result
            );
        } catch (\Mockery\Exception\BadMethodCallException $e) {
            $this->assertContains('does not exist on this mock object', $e->getMessage());
        }
    }

    public function testWhenGivenMethodExists()
    {
        $expected = [
            'result' => true,
            'message' => 'Request is well treated.',
        ];
        $this->action->shouldReceive([
            $this->method . 'Action' => $expected,
        ]);
        $this->assertSame(
            $expected,
            $this->action->dispatchAction($this->method, $this->parameters)
        );
    }
}
