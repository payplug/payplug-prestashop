<?php

namespace PayPlug\tests\actions\RequestAction;

/**
 * @group unit
 * @group action
 * @group request_action
 */
class applepayPatchActionTest extends BaseRequestAction
{
    public $params;

    public function setUp()
    {
        parent::setUp();
        $this->params = [
            'pay_id' => 'pay_id',
            'token' => ['token'],
            'workflow' => 'workflow',
            'carrier' => ['carrier'],
            'user' => ['user'],
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $params
     */
    public function testWhenGivenParamsIsInvalidArrayFormat($params)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $params must be a non empty array.',
            ],
            $this->action->applepayPatchAction($params)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenParamsResourceIdIsInvalidStringFormat($resource_id)
    {
        $params = $this->params;
        $params['pay_id'] = $resource_id;
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ],
            $this->action->applepayPatchAction($params)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $token
     */
    public function testWhenGivenParamsTokenIsInvalidArrayFormat($token)
    {
        $params = $this->params;
        $params['token'] = $token;

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $token must be a non empty array.',
            ],
            $this->action->applepayPatchAction($params)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $workflow
     */
    public function testWhenGivenParamsWorkflowIsInvalidStringFormat($workflow)
    {
        $params = $this->params;
        $params['workflow'] = $workflow;

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $workflow must be a non empty string.',
            ],
            $this->action->applepayPatchAction($params)
        );
    }

    public function testWhenPatchReturnAnError()
    {
        $error_returned = [
            'result' => false,
            'message' => 'Error returned.',
        ];
        $this->payment_method->shouldReceive([
            'patchPaymentResource' => $error_returned,
        ]);
        $this->assertSame(
            $error_returned,
            $this->action->applepayPatchAction($this->params)
        );
    }

    public function testWhenPaymentIsWellPatched()
    {
        $patch = [
            'result' => true,
            'return_url' => 'succes url',
            'message' => 'Success',
        ];
        $this->payment_method->shouldReceive([
            'patchPaymentResource' => $patch,
        ]);
        $this->assertSame(
            $patch,
            $this->action->applepayPatchAction($this->params)
        );
    }
}
