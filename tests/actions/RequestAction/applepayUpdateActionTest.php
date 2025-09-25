<?php

namespace PayPlug\tests\actions\RequestAction;

/**
 * @group unit
 * @group action
 * @group request_action
 *
 * @runTestsInSeparateProcesses
 */
class applepayUpdateActionTest extends BaseRequestAction
{
    public $request;

    public function setUp()
    {
        parent::setUp();
        $this->request = [
            'key' => 'value',
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $request
     */
    public function testWhenApplepayRequestReturnedIsEmpty($request)
    {
        $this->payment_method->shouldReceive([
            'getRequest' => $request,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'request' => $request,
            ],
            $this->action->applepayUpdateAction()
        );
    }

    public function testWhenApplepayRequestReturnedIsValid()
    {
        $this->payment_method->shouldReceive([
            'getRequest' => $this->request,
        ]);
        $this->assertSame(
            [
                'result' => true,
                'request' => $this->request,
            ],
            $this->action->applepayUpdateAction()
        );
    }
}
