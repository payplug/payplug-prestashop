<?php

namespace PayPlug\tests\actions\CardAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group card_action
 *
 * @runTestsInSeparateProcesses
 */
class renderOrderDetailTest extends BaseCardAction
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment
     */
    public function testWhenGivenPaymentIsInvalidObjectFormat($payment)
    {
        $this->assertSame(
            [],
            $this->action->renderOrderDetail($payment)
        );
    }

    public function testWhenCardInformationIsRender()
    {
        $payment = PaymentMock::getOneClick();
        $this->assertSame(
            [
                'last4' => '0001',
                'country' => 'FR',
                'exp_year' => 2030,
                'exp_month' => 9,
                'brand' => 'CB',
            ],
            $this->action->renderOrderDetail($payment)
        );
    }
}
