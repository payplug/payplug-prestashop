<?php

namespace PayPlug\tests\actions\HookAction;

/**
 * @group unit
 * @group action
 * @group hook_action
 */
class createPaymentLinkActionTest extends BaseHookAction
{
    private $order_state_id;
    private $id_cart;

    public function setUp()
    {
        parent::setUp();

        $this->order_state_id = 1;
        $this->id_cart = 2;
        $this->configuration->shouldReceive([
            'set' => true,
        ]);
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_state_id
     */
    public function testWhenGivenOrderStateIdIsInvalidIntegerFormat($order_state_id)
    {
        $this->assertFalse($this->action->createPaymentLinkAction($order_state_id, $this->id_cart));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testWhenGivenIdCartIsInvalidIntegerFormat($id_cart)
    {
        $this->assertFalse($this->action->createPaymentLinkAction($this->order_state_id, $id_cart));
    }

    public function testWhenNoMethodFoundFromOrderStateId()
    {
        $this->configuration->shouldReceive([
            'getValue' => 'not_payment_link_status',
        ]);
        $this->assertTrue($this->action->createPaymentLinkAction($this->order_state_id, $this->id_cart));
    }

    public function testWhenPaymentLinkStoredResourceExists()
    {
        $this->configuration->shouldReceive([
            'getValue' => $this->order_state_id,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'method' => 'email_link',
            ],
        ]);
        $this->assertTrue($this->action->createPaymentLinkAction($this->order_state_id, $this->id_cart));
    }

    public function testWhenTheResourceIsNotCreated()
    {
        $this->configuration->shouldReceive([
            'getValue' => $this->order_state_id,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->action->shouldReceive([
            'setContextFromCartId' => true,
        ]);
        $this->payment_action->shouldReceive([
            'dispatchAction' => [],
        ]);
        $this->assertFalse($this->action->createPaymentLinkAction($this->order_state_id, $this->id_cart));
    }

    public function testWhenTheResourceIsCreated()
    {
        $this->configuration->shouldReceive([
            'getValue' => $this->order_state_id,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->action->shouldReceive([
            'setContextFromCartId' => true,
        ]);
        $this->payment_action->shouldReceive([
            'dispatchAction' => [
                'result' => true,
            ],
        ]);
        $this->assertTrue($this->action->createPaymentLinkAction($this->order_state_id, $this->id_cart));
    }
}
