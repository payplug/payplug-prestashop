<?php

namespace PayPlug\tests\actions\OneyAction;

/**
 * @group unit
 * @group action
 * @group oney_action
 *
 * @runTestsInSeparateProcesses
 */
class renderScheduleTest extends BaseOneyAction
{
    public function setup()
    {
        parent::setUp();
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $param
     * @param mixed $oney_payment
     */
    public function testWhenGivenOneyPaymentIsInvalidArrayFormat($oney_payment)
    {
        $amount = 147.20;

        $this->assertSame(
            false,
            $this->action->renderSchedule($oney_payment, $amount)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsInvalidFloatFormat($amount)
    {
        $oney_payment = [];

        $this->assertSame(
            false,
            $this->action->renderSchedule($oney_payment, $amount)
        );
    }
}
