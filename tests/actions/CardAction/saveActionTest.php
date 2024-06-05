<?php

namespace PayPlug\tests\actions\CardAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group card_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class saveActionTest extends BaseCardAction
{
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_id')
            ->andReturn(42);
        $this->payment = PaymentMock::getOneClick();
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment
     */
    public function testWhenGivenPaymentIsInvalidObjectFormat($payment)
    {
        $this->assertFalse($this->action->saveAction($payment));
    }

    public function testWhenCardAllrealdyExists()
    {
        $this->card_repository
            ->shouldReceive([
                'exists' => true,
            ]);
        $this->assertFalse($this->action->saveAction($this->payment));
    }

    public function testWhenCardCantBeRegistered()
    {
        $this->card_repository
            ->shouldReceive([
                'exists' => false,
                'createCard' => false,
            ]);
        $this->assertFalse($this->action->saveAction($this->payment));
    }

    public function testWhenCardIsRegistered()
    {
        $this->card_repository
            ->shouldReceive([
                'exists' => false,
                'createCard' => true,
            ]);
        $this->assertTrue($this->action->saveAction($this->payment));
    }
}
