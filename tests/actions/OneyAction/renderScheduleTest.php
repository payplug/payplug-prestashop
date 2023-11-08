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
     * @description  test invalid $oney_payment param
     * in renderSchedule
     *
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $oney_payment
     */
    public function testWhenGivenOneyPaymentIsInvalidArrayFormat($oney_payment)
    {
        $controller = $this->instance->shouldReceive([
                                                         'getController' => 'product',
                                                     ]);
        $this->dispatcher->shouldReceive([
                                             'getInstance' => $controller,
                                         ]);
        $amount = 147.20;

        $this->assertSame(
            false,
            $this->action->renderSchedule($oney_payment, $amount)
        );
    }

    /**
     * @description test invalid $amount
     * param in renderSchedule
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsInvalidFloatFormat($amount)
    {
        $controller = $this->instance->shouldReceive([
                                                         'getController' => 'product',
                                                     ]);
        $this->dispatcher->shouldReceive([
                                             'getInstance' => $controller,
                                         ]);
        $oney_payment = [];

        $this->assertSame(
            false,
            $this->action->renderSchedule($oney_payment, $amount)
        );
    }
}
