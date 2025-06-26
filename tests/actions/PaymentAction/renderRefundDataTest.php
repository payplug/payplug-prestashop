<?php

namespace PayPlug\tests\actions\PaymentAction;

/**
 * @group unit
 * @group action
 * @group payment_action
 */
class renderRefundDataTest extends BasePaymentAction
{
    public $amount_refunded_payplug;
    public $amount_available;

    public function setUp()
    {
        parent::setUp();

        $this->amount_refunded_payplug = 42;
        $this->amount_available = 4242;
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount_refunded_payplug
     */
    public function testWhenGivenAmountRefundedPayplugIsntValidInteger($amount_refunded_payplug)
    {
        $this->assertSame(
            '',
            $this->action->renderRefundData($amount_refunded_payplug, $this->amount_available)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount_available
     */
    public function testWhenGivenAmountAvailableIsntValidInteger($amount_available)
    {
        $this->assertSame(
            '',
            $this->action->renderRefundData($this->amount_refunded_payplug, $amount_available)
        );
    }
}
