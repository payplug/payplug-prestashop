<?php

namespace PayPlug\tests\actions\PaymentAction;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class renderTemplateTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order
     */
    public function testWhenGivenIdOrderIsntValidInteger($id_order)
    {
        $this->assertSame(
            '',
            $this->action->renderTemplate($id_order)
        );
    }
}
