<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class removeActionTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsNotValidString($resource_id)
    {
        $cancellable = true;
        $this->assertFalse(
            $this->action->removeAction($resource_id, $cancellable)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $cancellable
     */
    public function testWhenGivenCancellableIsNotValidString($cancellable)
    {
        $resource_id = 'pay_azerty';
        $this->assertFalse(
            $this->action->removeAction($resource_id, $cancellable)
        );
    }

    public function testWhenResourceCantBeRetrieve()
    {
        $resource_id = 'pay_azerty12345';
        $cancellable = true;
        $api_class = \Mockery::mock('ApiClass');
        $api_class
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies->apiClass = $api_class;
        $this->assertFalse(
            $this->action->removeAction($resource_id, $cancellable)
        );
    }

    public function testWhenRetrievedResourceCantBeAbort()
    {
        $resource_id = 'pay_azerty12345';
        $cancellable = true;
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $api_class
            ->shouldReceive([
                'retrievePayment' => $resource,
                'abortPayment' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies->apiClass = $api_class;
        $this->assertFalse(
            $this->action->removeAction($resource_id, $cancellable)
        );
    }

    public function testWhenRetrievedResourceCantBeRemoveFromDataBase()
    {
        $resource_id = 'pay_azerty12345';
        $cancellable = true;
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $api_class
            ->shouldReceive([
                'retrievePayment' => $resource,
                'abortPayment' => $resource,
            ]);
        $this->dependencies->apiClass = $api_class;

        $this->payment_repository
            ->shouldReceive([
                'removeByResourceId' => false,
            ]);

        $this->assertFalse(
            $this->action->removeAction($resource_id, $cancellable)
        );
    }

    public function testWhenRetrievedResourceIsRemove()
    {
        $resource_id = 'pay_azerty12345';
        $cancellable = true;
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $api_class
            ->shouldReceive([
                'retrievePayment' => $resource,
                'abortPayment' => $resource,
            ]);
        $this->dependencies->apiClass = $api_class;

        $this->payment_repository
            ->shouldReceive([
                'removeByResourceId' => true,
            ]);

        $this->assertTrue(
            $this->action->removeAction($resource_id, $cancellable)
        );
    }
}
